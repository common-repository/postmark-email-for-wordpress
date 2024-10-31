<?php
/*
Plugin Name: Postmark Email for WordPress
Version: 0.3.2
Plugin URI: http://yoast.com/wordpress/postmark-email-plugin/
Description: Divert all WordPress mail to <a href="http://postmarkapp.com">Postmarkapp</a>.
Author: Joost de Valk
Author URI: http://yoast.com

Copyright 2011 Joost de Valk (email: joost@yoast.com)
*/

// Postmark settings
define('POSTMARKAPP_PLUGIN_DIR_PATH', plugin_dir_path(__FILE__) );

require_once( POSTMARKAPP_PLUGIN_DIR_PATH.'Postmark/Postmark.php');
	
/**
* Break $recipient into name and address parts if in the format "Foo <bar@baz.com>"
*
* @since 0.1
*
* @param string $recipient
* @return array array with strings name and email 
*/
function format_email_recipient( $recipient ) {
	$return = array( 
		'name' => '',
		'email' => $recipient
	);
	if( preg_match( '/(.*)<(.+)>/', $recipient, $matches ) ) {
		if ( count( $matches ) == 3 ) {
			$return['name'] = $matches[1];
			$return['email'] = $matches[2];
		}
	}
	return $return;
}

// Wrapper needed because this uses the very annoying concept of pluggable functions.
if ( !function_exists('wp_mail') && defined('POSTMARKAPP_API_KEY') ) {

	/**
	 * Send mail, replacing WP's internal normal wp_mail function
	 *
	 * A true return value does not automatically mean that the user received the
	 * email successfully. It just only means that the method used was able to
	 * process the request without any errors.
	 *
	 * Using the two 'wp_mail_from' and 'wp_mail_from_name' hooks allow from
	 * creating a from address like 'Name <email@address.com>' when both are set. If
	 * just 'wp_mail_from' is set, then just the email address will be used with no
	 * name. Note that the email address has to be verified as a sender signature in Postmarkapp.
	 *
	 * The default content type is 'text/plain' which does not allow using HTML.
	 * However, you can set the content type of the email by using the
	 * 'wp_mail_content_type' filter.
	 *
	 * The default charset is based on the charset used on the blog. The charset can
	 * be set using the 'wp_mail_charset' filter.
	 *
	 * @since 1.2.1
	 * @uses apply_filters() Calls 'wp_mail' hook on an array of all of the parameters.
	 * @uses apply_filters() Calls 'wp_mail_from' hook to get the from email address.
	 * @uses apply_filters() Calls 'wp_mail_from_name' hook to get the from address name.
	 * @uses apply_filters() Calls 'wp_mail_content_type' hook to get the email content type.
	 *
	 * @param string|array $to Array or comma-separated list of email addresses to send message.
	 * @param string $subject Email subject
	 * @param string $message Message contents
	 * @param string|array $headers Optional. Additional headers.
	 * @param string|array $attachments Optional. Files to attach.
	 * @return bool Whether the email contents were sent successfully.
	 */
	function wp_mail( $to, $subject, $message, $headers = '', $attachments = array() ) {
	
		// Compact the input, apply the filters, and extract them back out
		extract( apply_filters( 'wp_mail', compact( 'to', 'subject', 'message', 'headers', 'attachments' ) ) );
	
		if ( !is_array($attachments) )
			$attachments = explode( "\n", str_replace( "\r\n", "\n", $attachments ) );
	
		$email = new Mail_Postmark();
	
		// Headers
		if ( empty( $headers ) ) {
			$headers = array();
		} else {
			if ( !is_array( $headers ) ) {
				// Explode the headers out, so this function can take both
				// string headers and an array of headers.
				$tempheaders = explode( "\n", str_replace( "\r\n", "\n", $headers ) );
			} else {
				$tempheaders = $headers;
			}
			$headers 	= array();
			$cc 		= array();
			$bcc 		= array();
	
			// If it's actually got contents
			if ( !empty( $tempheaders ) ) {
				// Iterate through the raw headers
				foreach ( (array) $tempheaders as $header ) {
					if ( strpos($header, ':') === false ) {
						if ( false !== stripos( $header, 'boundary=' ) ) {
							$parts = preg_split('/boundary=/i', trim( $header ) );
							$boundary = trim( str_replace( array( "'", '"' ), '', $parts[1] ) );
						}
						continue;
					}
					// Explode them out
					list( $name, $content ) = explode( ':', trim( $header ), 2 );
	
					// Cleanup crew
					$name    = trim( $name    );
					$content = trim( $content );
	
					switch ( strtolower( $name ) ) {
						// Mainly for legacy -- process a From: header if it's there
						case 'from':
							if ( strpos($content, '<' ) !== false ) {
								// So... making my life hard again?
								$from_name = substr( $content, 0, strpos( $content, '<' ) - 1 );
								$from_name = str_replace( '"', '', $from_name );
								$from_name = trim( $from_name );
	
								$from_email = substr( $content, strpos( $content, '<' ) + 1 );
								$from_email = str_replace( '>', '', $from_email );
								$from_email = trim( $from_email );
							} else {
								$from_email = trim( $content );
							}
							break;
						case 'content-type':
							// We don't currently use the charset, but we need the content type to determine which type of mail to set.
							if ( strpos( $content, ';' ) !== false ) {
								list( $type, $charset ) = explode( ';', $content );
								$content_type = trim( $type );
								if ( false !== stripos( $charset, 'charset=' ) ) {
									$charset = trim( str_replace( array( 'charset=', '"' ), '', $charset ) );
								} elseif ( false !== stripos( $charset, 'boundary=' ) ) {
									$boundary = trim( str_replace( array( 'BOUNDARY=', 'boundary=', '"' ), '', $charset ) );
									$charset = '';
								}
							} else {
								$content_type = trim( $content );
							}
							break;
						case 'cc':
							$cc = array_merge( (array) $cc, explode( ',', $content ) );
							break;
						case 'bcc':
							$bcc = array_merge( (array) $bcc, explode( ',', $content ) );
							break;
						case 'reply-to':
							$reply_to = $content;
							break;
						default:
							// Add it to our grand headers array
							$headers[trim( $name )] = trim( $content );
							break;
					}
				}
			}
		}
	
		// From email and name
		// If we don't have a name from the input headers
		if ( !isset( $from_name ) )
			$from_name = 'WordPress';
	
		// Plugin authors can override the potentially troublesome default
		$email->from( 	  apply_filters( 'wp_mail_from'     , $from_email ) );
		$email->fromName( apply_filters( 'wp_mail_from_name', $from_name ) );

		// If the from name is not in your list of allowed sender email addresses, use the first allowed sender email address and move the from name and email to the reply to.
		$allowedfrom = explode(',',POSTMARKAPP_MAIL_FROM_ADDRESS);
		if ( !in_array( $from_email, $allowedfrom ) ) {
			$reply_to = $from_name.' <'.$from_email.'>';
			$from_name = POSTMARKAPP_MAIL_FROM_NAME;
			$from_email = $allowedfrom[0];
		}
		
		if ( ( $from_name == '' || $from_name == $from_email ) && defined('POSTMARKAPP_MAIL_FROM_NAME') ) 
			$from_name = POSTMARKAPP_MAIL_FROM_NAME;
	
		// Set destination addresses
		if ( !is_array( $to ) )
			$to = explode( ',', $to );
	
		foreach ( (array) $to as $recipient ) {
			$recipient = format_email_recipient( $recipient );
			$email->addTo( $recipient['email'], $recipient['name'] );
		}
	
		// Set mail's subject and body
		$email->subject( $subject );
	
		// Set Content-Type and charset
		// If we don't have a content-type from the input headers
		if ( !isset( $content_type ) )
			$content_type = 'text/plain';
	
		$content_type = apply_filters( 'wp_mail_content_type', $content_type );
	
		if ( $content_type == 'text/html' )
			$email->messageHtml( $message );
		else
			$email->messagePlain( $message );
	
		// Add any CC and BCC recipients
		if ( !empty( $cc ) ) {
			foreach ( (array) $cc as $recipient ) {
				$recipient = format_email_recipient( $recipient );
				$email->addCc( $recipient['email'], $recipient['name'] );
			}
		}
	
		if ( !empty( $bcc ) ) {
			foreach ( (array) $bcc as $recipient) {
				$recipient = format_email_recipient( $recipient );
				$email->addBcc( $recipient['email'], $recipient['name'] );
			}
		}
	
		// Set the reply to
		if ( isset( $reply_to ) ) {
			$reply_to = format_email_recipient( $reply_to );
			$email->replyTo( $reply_to['email'], $reply_to['name'] );
		}
	
		// Set custom headers
		if ( !empty( $headers ) ) {
			foreach( (array) $headers as $name => $content ) {
				$email->addHeader( $name, $content );
			}
		}
	
		if ( !empty( $attachments ) ) {
			foreach ( $attachments as $attachment ) {
				$email->addAttachment($attachment);
			}
		}
	
		$email->send();
	}

}