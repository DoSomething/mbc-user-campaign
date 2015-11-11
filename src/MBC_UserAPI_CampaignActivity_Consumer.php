<?php
/*
 * MBC_UserAPICampaignActivity.class.in: Used to process the transactionalQueue
 * entries that match the campaign.*.* binding.
 */

namespace DoSomething\MBC_UserAPI_CampaignActivity;

use DoSomething\MB_Toolbox\MB_Configuration;
use DoSomething\MBStatTracker\StatHat;
use DoSomething\MB_Toolbox\MB_Toolbox_BaseConsumer;
use DoSomething\MB_Toolbox\MB_Toolbox_cURL;
use \Exception;

/**
 * MBC_UserAPI_CampaignActivity_Consumer class - functionality to process message entries in
 * userAPICampaignActivityQueue. Message create POSTs to mb-user-api /user.
 */

class MBC_UserAPI_CampaignActivity_Consumer extends MB_Toolbox_BaseConsumer
{

  /**
   * cURL object to access cUrl related methods
   * @var object $mbToolboxcURL
   */
  protected $mbToolboxcURL;

  /**
   * The URLto POST to.
   * @var string $curlUrl
   */
  private $curlUrl;

  /**
   * The composed submission for POSTing to mb-user-api.
   * @var string $submission
   */
  private $submission;

  /**
   * __construct(): Common values for class. The base class MB_Toolbox_BaseConsumer also
   * contains properties in __construct().
   */
  public function __construct() {

    parent::__construct();
    $this->mbConfig = MB_Configuration::getInstance();
    $this->mbToolboxcURL = $this->mbConfig->getProperty('mbToolboxcURL');
    $mbUserAPI = $this->mbConfig->getProperty('mb_user_api_config');
    $this->curlUrl = $mbUserAPI['host'];
    if (isset($mbUserAPI['port'])) {
      $this->curlUrl .= ':' . $mbUserAPI['port'];
    }
    $this->curlUrl .= '/user';
  }

   /**
   * Callback for messages arriving in the userAPICampaignActivityQueue.
   *
   * @param string $payload
   *   A serialized message to be processed.
   */
  public function consumeUserAPICampaignActivityQueue($payload) {

    echo '-------  mbc-userAPI-campaignActivity -  MBC_UserAPI_CampaignActivity_Consumer->consumeUserAPICampaignActivityQueue() START -------', PHP_EOL;

    parent::consumeQueue($payload);
    echo '** Consuming: ' . $this->message['email'], PHP_EOL;

    if ($this->canProcess()) {

      try {

        $this->setter($this->message);
        $this->process();
      }
      catch(Exception $e) {
        echo 'Error submitting user campaign activity for email address: ' . $this->message['email'] . ' to mb-user-api. Error: ' . $e->getMessage();
        $this->messageBroker->sendAck($this->message['payload']);
      }

    }
    else {
      echo '=> ' . $this->message['email'] . ' can\'t be processed.', PHP_EOL;
      $this->messageBroker->sendAck($this->message['payload']);
    }

    echo '-------  mbc-userAPI-campaignActivity -  MBC_UserAPI_CampaignActivity_Consumer->consumeUserAPICampaignActivityQueue() END -------', PHP_EOL . PHP_EOL;
  }

  /**
   * Conditions to test before processing the message.
   *
   * @return boolean
   */
  protected function canProcess() {

    if (!(isset($this->message['email']))) {
      echo '- canProcess(), email not set.', PHP_EOL;
      return FALSE;
    }
    // Don't process 1234@mobile email address (legacy hack in Drupal app to support mobile registrations)
    // BUT allow processing email addresses: joe@mobilemaster.com
    $mobilePos = strpos($this->message['email'], '@mobile');
    if ($mobilePos > 0 && (strlen($this->message['email']) - $mobilePos) > 7) {
      echo '- canProcess(), Drupal app fake @mobile email address.', PHP_EOL;
      return FALSE;
    }

    if (!(isset($this->message['activity']))) {
      echo '- canProcess(), activity not set.', PHP_EOL;
      return FALSE;
    }
    if (isset($this->message['activity'])) {
      if ($this->message['activity'] != 'campaign_signup' || $this->message['activity'] != 'campaign_reportback') {
        echo '- canProcess(), not campaign_signup or campaign_reportback activity.', PHP_EOL;
        return FALSE;
      }
    }

    if (!(isset($this->message['activity_timestamp']))) {
      echo '- canProcess(), activity_timestamp not set.', PHP_EOL;
      return FALSE;
    }
    if (isset($this->message['activity_timestamp']) && (!(is_int($this->message['activity_timestamp'])))) {
      echo '- canProcess(), activity_timestamp not valid.', PHP_EOL;
      return FALSE;
    }

    return TRUE;

  }

  /**
   * Construct values for submission to mb-users-api service.
   *
   * @param array $message
   *   The message to process based on what was collected from the queue being processed.
   */
  protected function setter($message) {

  }

  /**
   * process(): POST formatted message values to mb-users-api /user.
   */
  protected function process() {

    echo '-> post: ' . print_r($this->submission, TRUE) . ' - ' . date('j D M Y G:i:s Y') . ' -------', PHP_EOL;

    $results = $this->mbToolboxcURL->curlPOST($this->curlUrl, $this->submission);
    if ($results[1] == 200) {
      $this->messageBroker->sendAck($this->message['payload']);
    }
    else {
      echo '- mb-user-api ERROR: ' . print_r($results[0], TRUE), PHP_EOL;
      throw new Exception('Error submitting campaign activity to mb-user-api: ' . print_r($this->submission, TRUE));
    }
  }

}
