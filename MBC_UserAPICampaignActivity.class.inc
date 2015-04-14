<?php
/*
 *
 */

class MBC_UserAPICampaignActivity
{

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

    $userApiUrl = getenv('DS_USER_API_HOST') . ':' . getenv('DS_USER_API_PORT') . '/user';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $userApiUrl);
    curl_setopt($ch, CURLOPT_POST, count($post));
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($ch);
    curl_close($ch);

  }

}