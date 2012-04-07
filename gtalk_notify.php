<?php
/*
 * gtalk_notify.php
 *
 * This is example code of how to get webhook notifications from BeanstalkApp.
 * 
 * @link http://thecodecave.com/scripts/google-talk-notifications-beanstalkapp-example/
 * 
 * @author Brian Layman <http://eHermitsInc.com/>
 * @license GPL-2.0+
 * 
 * Pages hit during development:
 * http://support.beanstalkapp.com/customer/portal/articles/68163-web-hooks-for-deployments
 * http://www.ibm.com/developerworks/xml/tutorials/x-alertxmpptut/section4.html
 * http://www.sencha.com/forum/archive/index.php/t-178193.html?s=3519335403211df2b89c70ae20ce811f
 * http://php.net/manual/en/wrappers.php.php
 * http://code.google.com/p/xmpphp/
 * http://support.google.com/talk/bin/answer.py?hl=en&answer=27930
 * 
 * This project requires the XMPPHP project. It's not been updated in 3 years, since 2009, so the 
 * link to the latest and greatest will probably always be:
 * http://xmpphp.googlecode.com/files/xmpphp-0.1rc2-r77.tgz
 * Extract the contents of that archive and move the directory named XMPPHP to location where this 
 * file will live on your webserver. 
 * 
 * Change the constants below and then set the post deployment notification for your project.
 * This script can be used for multiple projects. No alteration is required.
 *
 * IMPORTANT!  This script requires TCP port 5222 to be open outbund on your server.
 * If this port is closed you will get "Could not connect before time out" errors.
 *
 * IMPORTANT!  Google Talk works off of invites.  So you MUST have chatting between source and 
 * destination working before you use this script.
 *
 * 
 * License:
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * a long with this program. If not, see <http://www.gnu.org/licenses/>.
 * 
*/


define( 'SOURCE_USERNAME', 'ChangeMe@gmail.com' );
define( 'SOURCE_PASSWORD', '0bvi0slyN0tS3cur3' );
define( 'DESTINATION', 'example@gmail.com' );

require_once("XMPPHP/XMPP.php");
class GTalk {
    private $conn;

    function __construct() {
         $this->conn = new XMPPHP_XMPP('talk.google.com', 5222,
            SOURCE_USERNAME, SOURCE_PASSWORD, 'xmpphp', 'gmail.com', 
            $printLog=false, $loglevel=0);
    }

    function connect() {
        try {
            $this->conn->connect();
            $this->conn->processUntil('session_start');
        } catch(XMPPHP_Exception $e) {
            die($e->getMessage());
        }
    }

    function disconnect() {
        try {
            $this->conn->disconnect();
        } catch(XMPPHP_Exception $e) {
            die($e->getMessage());
        }
    }

    function send_message($to, $msg) {
        $this->connect();
        try {
            $this->conn->message($to, $msg);
        } catch(XMPPHP_Exception $e) {
            die($e->getMessage());
        }
        $this->disconnect();
    }
}

// Receive data directly from the server (not via $_post)
$raw = '';
$httpContent = fopen( 'php://input', 'r' );
while ( $kb = fread( $httpContent, 1024 ) ) {
    $raw .= $kb;
}

// Uncomment this line for testing
// $raw = '{"author":"author username", "repository":"beanstalk", "author_name":"John Smith","comment":"example","author_email":"johnsmith@example.com","server":"server example","environment":"development","revision":"5","deployed_at":"deployed at date","repository_url":"git@example.beanstalkapp.com:/example.git","source":"beanstalkapp"}';

$notification = json_decode( stripslashes( $raw ) );


/*
This script expects the following JSON to be sent to it from BeanStalkApp. However, you can adjust the msg to display anything. 

{
     "author":"author username", // username of the author
     "repository":"beanstalk", // repository from which data was or will be deployed depending on whether it's a pre or post hook
     "author_name":"John Smith", // full name of the author
     "comment":"example", // commit message
     "author_email":"johnsmith@example.com", // email of the author
     "server":"server example", // server to which data was deployed or will be deployed depending on whether it's a pre or post hook
     "environment":"development", //environment from which data was or will be deployed depending on whether it's a pre or post hook
     "revision":"5", // revision to which the deployment will be updated
     "deployed_at":"deployed at date",  // time when deployment happened - timezone is included, will be null for pre hook
     "repository_url":"git@example.beanstalkapp.com:/example.git or https://example.svn.beanstalk.com/example", // source control url of the repository
     "source":"beanstalkapp"  // identifier of the payload, in case you consume JSON from many vendors
}   

*/


// Build the message
if ( $notification ) {
	if ( is_null( $notification->deployed_at ) ) {
		$msg = 'BeanStalk Pre-Deploy - ' . $notification->repository . ' - Revision ' . $notification->revision . ' by ' . $notification->author_name . ' to be deployed. Comment: ' . $notification->comment;
	} else {
		$msg = 'BeanStalk Deploy - ' . $notification->repository . ' - Revision ' . $notification->revision . ' by ' . $notification->author_name . ' deployed. Comment: ' . $notification->comment;
	}	
} else {
  // Fail Silently
  exit;
}

// Now Send the Message
$gtalk = new GTalk;
$gtalk->send_message( DESTINATION, $msg );
// echo "1";