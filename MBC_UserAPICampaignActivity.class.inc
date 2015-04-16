<?php
/*
 * MBC_UserAPICampaignActivity.class.in: Used to process the transactionalQueue
 * entries that match the campaign.*.* binding.
 */

use DoSomething\MB_Toolbox\MB_Toolbox;
use DoSomething\MBStatTracker\StatHat;

class MBC_UserAPICampaignActivity
{

  /**
   * Message Broker connection to RabbitMQ
   */
  private $messageBroker;

  /**
   * Setting from external services - Mailchimp.
   *
   * @var array
   */
  private $settings;

  /**
   * Setting from external services - Mailchimp.
   *
   * @var array
   */
  private $statHat;

  /**
   * Constructor for MBC_TransactionalEmail
   *
   * @param array $settings
   *   Settings from external services - StatHat
   */
  public function __construct($messageBroker, $settings) {

    $this->messageBroker = $messageBroker;
    $this->settings = $settings;

    // Stathat
    $this->statHat = new StatHat($this->settings['stathat_ez_key'], 'mbc-userAPI-campaignActivity:');
    $this->statHat->setIsProduction(TRUE);
  }

  /**
   * Submit user campaign activity to the UserAPI
   *
   * @param array $payload
   *   The contents of the queue entry
   */
  public function updateUserAPI($payload) {

    $payloadDetails = unserialize($payload->body);

    // There will only ever be one campaign entry in the payload
    $post = array(
      'email' => $payloadDetails['email'],
      'subscribed' => 1,
      'campaigns' => array(
        0 => array(
          'nid' => $payloadDetails['event_id'],
        ),
      )
    );

    if (!(isset($payloadDetails['activity_timestamp']) && $payloadDetails['activity_timestamp'] != '' && is_int($payloadDetails['activity_timestamp']))) {
      echo 'Invalid activity_timestamp value: ' . print_r($payloadDetails, TRUE), PHP_EOL;
      $payloadDetails['activity_timestamp'] = time();
    }

    // Campaign signup or reportback?
    if ($payloadDetails['activity'] == 'campaign_reportback') {
      $post['campaigns'][0]['reportback'] = $payloadDetails['activity_timestamp'];
    }
    else {
      $post['campaigns'][0]['signup'] = $payloadDetails['activity_timestamp'];
    }

    echo '------- mbc-userAPI-campaignActivity - MBC_UserAPICampaignActivity: $post: ' . print_r($post, TRUE) . ' - ' . date('D M j G:i:s T Y') . ' -------', PHP_EOL;

    $curlUrl = $this->settings['ds_user_api_host'];
    $port = $this->settings['ds_user_api_port'];
    if ($port != 0) {
      $curlUrl .= ":$port";
    }
    $curlUrl .= '/user';
    $result = $this->toolbox->curlPOST($curlUrl, $post);

    if ($result == TRUE) {
      echo 'Updated campaign activity for email: ' . $post['email'], PHP_EOL;
      $this->statHat->addStatName('success');
    }
    else {
      echo 'FAILED to update campaign activity for emai: ' . $post['email'], PHP_EOL;
      $this->statHat->addStatName('update failed');
    }
  }

}
