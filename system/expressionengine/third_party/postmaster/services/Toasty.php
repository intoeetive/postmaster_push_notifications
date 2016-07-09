<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Prowl
 *
 * Allows you to send Toasty push notifications
 *
 * @author		Yuri Salimovskiy
 * @link 		http://www.intoeetive.com/
 * @version		1.0
 */
 

class Toasty_postmaster_service extends Base_service {

	public $name = 'Toasty';
    
    public $url = "http://api.supertoasty.com/notify/";
	
	public $default_settings = array(

	);

	public $fields = array(

	);

	public $description = 'Send Push notification to Windows Phone device using Toasty service. "To email" field should contain recipient Device ID.';

	public function __construct()
	{
		parent::__construct();
	}



	public function send($parsed_object, $parcel)
	{		
		$settings = $this->get_settings();

		$message = strip_tags($parsed_object->message);

		if(isset($parsed_object->plain_message) && !empty($parsed_object->plain_message))
		{
			$message = $parsed_object->plain_message;
		}

        $post = array(
            'sender'=> ($parsed_object->from_name!='')?$parsed_object->from_name:$this->EE->config->item('site_name'),
			'title'      => $parsed_object->subject,
			'text'=> $message
		);
        
        $fields_string = '';
        foreach($post as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
        $fields_string = rtrim($fields_string,'&');   
        
        $ch = curl_init($this->url.$parsed_object->to_email);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC ) ;
        curl_setopt($ch, CURLOPT_SSLVERSION,3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array());
        
        curl_setopt($ch,CURLOPT_POST,true);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$fields_string);

        $response = curl_exec($ch);
        curl_close($ch);

        /*
        $this->curl->create($this->url.$parsed_object->to_email);
		$this->curl->option(CURLOPT_CUSTOMREQUEST, 'POST');
		$this->curl->option(CURLOPT_RETURNTRANSFER, TRUE);
		$this->curl->post($post);

		$response = $this->curl->execute();
  
        $this->EE->load->library('xmlparser');
		$xml = $this->EE->xmlparser->parse_xml($response);
        */

		return new Postmaster_Service_Response(array(
			'status'     => strpos($response, 'success')!==false ? POSTMASTER_SUCCESS : POSTMASTER_FAILED,
			'parcel_id'  => $parcel->id,
			'channel_id' => isset($parcel->channel_id) ? $parcel->channel_id : FALSE,
			'author_id'  => isset($parcel->entry->author_id) ? $parcel->entry->author_id : FALSE,
			'entry_id'   => isset($parcel->entry->entry_id) ? $parcel->entry->entry_id : FALSE,
			'gmt_date'   => $this->now,
			'service'    => $parcel->service,
			'to_name'    => $parsed_object->to_name,
			'to_email'   => $parsed_object->to_email,
			'from_name'  => $parsed_object->from_name,
			'from_email' => $parsed_object->from_email,
			'cc'         => $parsed_object->cc,
			'bcc'        => $parsed_object->bcc,
			'subject'    => $parsed_object->subject,
			'message'    => $parsed_object->message,
			'parcel'     => $parcel
		));
	}

	public function display_settings($settings, $parcel)
	{	
		return $this->build_table($settings);
	}
}