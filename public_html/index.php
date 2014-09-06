<?php
/**
 * Copyright (c) 2014 Adam L. Englander
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the Software
 * is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A
 * PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF
 * CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE
 * OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

// Init our db connection and other stuff
require_once '../bootstrap.php';

// Init our request/response objects
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use LVPHP\Models\Topic;

$request = Request::createFromGlobals();

$response = new Response(
    'Content',
    Response::HTTP_OK,
    array('content-type' => 'text/html')
);

// Initialize our variables used in the template
$errors = array();
$header = null;
$body = null;
$topics = null;
$upvote = null;
$responseContent = null;

// Add a generic try catch around the entire PHP code section to ensure we catch any errors
try {

    if ($request->isMethod('POST')) {

        // Check for upvote on topic
        $topic_id = $request->get('topic_id');
        if ($topic_id) {
            $topic = $entityManager->getRepository('LVPHP\Models\Topic')->findOneById($topic_id);
            $vote = new \LVPHP\Models\Vote($topic, $request->getClientIp());
            $entityManager->persist($vote);
            $entityManager->flush();

        } else {

            $header = $request->get('header');
            $body = $request->get('body');

            if (empty($header)) {
                $errors[] = 'Please enter a header for the topic.';
            }

            if (empty($body)) {
                $errors[] = 'A description of the topic is required.';
            }

            // If the form post had no errors then we will store the data in the database
            if (!$errors) {

                // Use try/catch to catch any errors in saving the registration
                try {
                    $topic = new \LVPHP\Models\Topic($header, $body, $request->getClientIp());
                    $vote = new \LVPHP\Models\Vote($topic, $request->getClientIp());
                    $entityManager->persist($vote);
                    $entityManager->flush();
                    // Since the topic was created successfully, clear the form values to allow for a new topic idea
                    $header = null;
                    $body = null;
                } catch (\Doctrine\ORM\ORMInvalidArgumentException $e) {
                    // Set status code to 500 due to server error
                    $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
                    $message = sprintf(
                        'Unable to save topic: %s: Topic info: [header: %s, description: %s]',
                        $e->getMessage(),
                        $header,
                        $body
                    );

                    // Throw new Exception with descriptive error message so the default handler will properly log an display the error
                    throw new Exception($message, 0, $e); // Set the caught exception as the previous
                }
            }
        }
    }

    // Get all the topics AFTER we made one or w/e
    $topics = $entityManager->getRepository('LVPHP\Models\Topic')->findBy(array('status' => Topic::ACTIVE));

} catch (Exception $e) {
    // The default/master exception handler will log the error and display to the user

    // Generate a Unique ID to identify this error
    $errorId = uniqid('ERROR-');

    // Add a nondescript error to the errors to show the user and include the error ID for reference
    $errors[] = sprintf('An application error occurred [%s]', $errorId);
    if ($isDevMode) {
        $errors[] = sprintf('Error message : %s $s Trace : %s', $e->getMessage(), PHP_EOL, $e->getTraceAsString());
    }
    error_log(sprintf('%s: %s', $errorId, $e->getMessage()));
}

$responseContent = '
<!DOCTYPE html>
<html>
<head>
<title>LVPHP.ORG</title>
<meta charset="UTF-8">
<meta name="description" content="Las Vegas PHP Users Group">
<meta name="keywords" content="Las Vegas PHP Users Group">
<meta name="author" content="lvphp.org">
<!-- JQUERY UI CSS -->
<link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.1/themes/smoothness/jquery-ui.css" /
<!-- Bootstrap CSS -->
<link rel="stylesheet" type="text/css" href="../css/bootstrap.min.css">
<link rel="stylesheet" href="../css/bootstrap-theme.min.css">
<link rel="stylesheet" href="../css/lvphp_custom.css">
</head>
<body>


<div class="container" id="page_container">
<!--NavBar Start     -->
<nav class="navbar navbar-default" role="navigation">
  <div class="container-fluid">
    <!-- Brand and toggle get grouped for better mobile display -->
    <div class="navbar-header">
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
        <span class="sr-only">Toggle navigation</span>

      </button>
      <a class="navbar-brand" href="#">lvphp.org</a>
    </div>

    <!-- Collect the nav links, forms, and other content for toggling -->
    <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
      <ul class="nav navbar-nav">
        <li class="active"><a href="#about">About Us</a></li>
        <li><a href="#meetup">Meetups</a></li>
         <li><a href="#topic_picker">Upcoming Topics</a></li>
          <li><a href="#sponsors">Sponsors</a></li>
        <li class="dropdown">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown">Dropdown <span class="caret"></span></a>
          <ul class="dropdown-menu" role="menu">
            <li><a href="#">Action</a></li>
            <li><a href="#">Another action</a></li>
            <li><a href="#">Something else here</a></li>
            <li class="divider"></li>
            <li><a href="#">Separated link</a></li>
            <li class="divider"></li>
            <li><a href="#">One more separated link</a></li>
          </ul>
        </li>
      </ul>
        </li>
      </ul>
    </div><!-- /.navbar-collapse -->
  </div><!-- /.container-fluid -->
</nav>
<!--NavBar End -->

<!-- Header Begin -->
<div id="top_header" class="document-header">
<div>
    <img src="http://upload.wikimedia.org/wikipedia/commons/thumb/archive/2/27/20100303222348%21PHP-logo.svg/120px-PHP-logo.svg.png" alt="PHP Logo">
    <h1>Las Vegas PHP User Group - LVPHP.org</h1>
</div>
</div>
<!-- Header End -->
<!-- About Us Begin -->
<div class="section-odd">
<a name="about" href="#about"></a>
<h1 class="section-header">About Us</h1>
<p class="about-text">The Las Vegas PHP Users Group is a group dedicated to PHP developers learning from and teaching each other.
Many PHP developers are experts in one segment or another. This group will be an opportunity for all of us to teach
hat we know well and learn what we do not. All skill levels are sought after. If you are looking to teach, learn, network,
or just mingle, join the group and participate on the adventure.</p>

</div>

<!-- About US End -->

<div class="section-even">
<a name="topic_picker" id="topic_picker" href="topic_picker"></a>
<div>
    <h1 class="section-header">Topics Picker</h1>
    <p>To suggest a topic, simply enter a title and description of what you want to hear about.</p>';

// If there are errors, display them to the user
if (!empty($errors)) {

    $responseContent .= '<div class="errors">
                            <h3 class="error-heading">Errors were encountered wth your topic</h3>
                            <ul>';

// Loop through the errors array
    foreach ($errors as $error) {
        $responseContent .= "<li>{$error}</li>";
    }
    $responseContent .= '</ul></div>';
}

$responseContent .= '<form method="POST">
        <div>
            <label for="header">Title: </label>
            <input id="header" name="header" value="'. htmlentities($header) .'">

            <label for="body">Description: </label>
            <input id="body" name="body" value="' . htmlentities($body) .'">

            <input type="submit" value="Create Topic">
        </div>
    </form>
</div>
<div class="topics-list">';

    if ($topics) {
        /**
         * @var $voteRepository LVPHP\Repositories\VoteRepository
         */
        $voteRepository = $entityManager->getRepository('LVPHP\Models\Vote');
        /**
         * @var $topic Topic
         */
        foreach ($topics as $topic) {
            $responseContent .= '<hr />';
            $responseContent .= '<h4> Title : ' . htmlentities($topic->getHeader()) . '</h4>';
            $responseContent .= '<p> Description : ' . htmlentities($topic->getBody()) . '</p>';
            $responseContent .= '<h4> Votes : ' . count($voteRepository->findAllVotesForTopic($topic)) . '</h4>';
            $vote = $voteRepository->findVoteFromTopicBasedOnIP($topic, $request->getClientIp());
            if (empty($vote)) {
                $responseContent .= '<form method="POST">
                                        <input type="hidden" name="topic_id" value="' . $topic->getId() .'">
                                        <input type="submit" value="Up Vote">
                                     </form>';
            }
        }
    } else {
        $responseContent .= '<h4>There are currently no topics.</h4>';
    }

$responseContent .= '</div></div>
<div class="section-odd">
<a name="sponsors" id="sponsors" href="#sponsors"></a>
<h1 class="section-header">Sponsors</h1>
<div class="row">
  <div class="col-sm-6 col-md-4">
    <div class="thumbnail">
      <img src="../img/logo_jetbrains.png" alt="Jetbrains">
      <div class="caption">
        <h3 class="thumbnail-title">JetBrains</h3>
        <p>JetBrains provides licenses for PHPStorm that are raffled away at events</p>
        <p><a href="http://www.jetbrains.com/" target="_blank" class="btn btn-primary" role="button">Learn More</a> </p>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-md-4">
    <div class="thumbnail">
      <img src="../img/logo_innovation.png" alt="InNEVation Center">
      <div class="caption">
        <h3 class="thumbnail-title">InNEVation Center</h3>
        <p>InNEVation Center provides space for our meetups</p>
         <p><a href="http://www.innevation.com/" target="_blank" class="btn btn-primary" role="button">Learn More</a> </p>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-md-4">
    <div class="thumbnail">
      <img src="../img/logo_coupla.jpg" alt="Coupla">
      <div class="caption">
        <h3 class="thumbnail-title">Thumbnail label</h3>
        <p>Coupla pays for our Meetup.com expenses</p>
        <p><a href="http://www.coupla.co/" target="_blank" class="btn btn-primary" role="button">Learn More</a> </p>
      </div>
    </div>
  </div>
</div>
</div>
</div>
</body>
<footer>
<!-- Jquery  -->
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.1/jquery-ui.min.js"></script>
<!--Bootstrap JS Files-->

<script type="text/javascript" src="../js/bootstrap.min.js"></script>
</footer>
</html>';

// Set and send response
$response->setContent($responseContent);
$response->send();