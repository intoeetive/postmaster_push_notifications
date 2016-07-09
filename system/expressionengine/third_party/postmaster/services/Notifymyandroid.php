<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Prowl
 *
 * Allows you to send Notify My Android push notifications
 *
 * @author		Yuri Salimovskiy
 * @link 		http://www.intoeetive.com/
 * @version		1.0
 */
 

class Notifymyandroid_postmaster_service extends Base_service {

	public $name = 'NotifyMyAndroid';
    public $title = 'Notify My Android';
    
    public $url = "https://www.notifymyandroid.com/publicapi/notify";
	
	public $default_settings = array(
        'api_key'	=> '',
        'priority'  => '0',
        'content_type' => 'text/plain'
	);

	public $fields = array(
		'api_key' => array(
			'label' => 'Provider API key'			
		),
        'priority' => array(
			'label' => 'Priority [-2, 2]'			
		),
        'content_type' => array(
			'label' => 'Content Type',
			'type'  => 'radio',
			'settings' => array(
				'options' => array(
					'text/plain'   => 'Text',
					'text/html'  => 'HTML',
				)
			)
		)
	);

	public $description = 'Send Push notification to mobile device using Notify My Android service. "To email" field should contain recipient API key.';

	public function __construct()
	{
		parent::__construct();
	}



	public function send($parsed_object, $parcel)
	{		
		$settings = $this->get_settings();
        
        if ($settings->content_type=='text/html')
        {
            $message = $parsed_object->message;
        }
        else
        {
    		$message = strip_tags($parsed_object->message);
    		if(isset($parsed_object->plain_message) && !empty($parsed_object->plain_message))
    		{
    			$message = $parsed_object->plain_message;
    		}
        }

        $post = array(
			'apikey'     => $parsed_object->to_email,
            'developerkey'=> $settings->api_key,
            'priority'   => $settings->priority,
			'url'        => $this->EE->config->item('site_url'),
            'application'=> ($parsed_object->from_name!='')?$parsed_object->from_name:$this->EE->config->item('site_name'),
			'event'      => $parsed_object->subject,
			'description'=> $message
		);
        if ($settings->content_type=='text/html')
        {
            $post['content-type'] = 'text/html';
        }

        $this->curl->create($this->url);
		$this->curl->option(CURLOPT_CUSTOMREQUEST, 'POST');
		$this->curl->option(CURLOPT_RETURNTRANSFER, TRUE);
		$this->curl->post($post);

		$response = $this->curl->execute();

        $this->EE->load->library('xmlparser');
		$xml = $this->EE->xmlparser->parse_xml($response);

		return new Postmaster_Service_Response(array(
			'status'     => $xml->children[0]->tag == 'success' ? POSTMASTER_SUCCESS : POSTMASTER_FAILED,
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