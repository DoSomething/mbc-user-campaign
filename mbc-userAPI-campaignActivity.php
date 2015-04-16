<?php
/**
 * mbc-user-campaign.php
 *
 * Collect user campaign activity from the userAPICampaignActivityQueue. Update the
 * UserAPI / database with user campaign activity.
 */

date_default_timezone_set('America/New_York');
// Load up the Composer autoload magic
require_once __DIR__ . '/vendor/autoload.php';

// Load configuration settings common to the Message Broker system
// symlinks in the project directory point to the actual location of the files
require __DIR__ . '/mb-secure-config.inc';
require __DIR__ . '/mb-config.inc';

require __DIR__ . '/MBC_UserAPICampaignActivity.class.inc';


// Settings
$credentials = array(
  'host' =>  getenv("RABBITMQ_HOST"),
  'port' => getenv("RABBITMQ_PORT"),
  'username' => getenv("RABBITMQ_USERNAME"),
  'password' => getenv("RABBITMQ_PASSWORD"),
  'vhost' => getenv("RABBITMQ_VHOST"),
);

$config = array(
  'exchange' => array(
    'name' => getenv("MB_TRANSACTIONAL_EXCHANGE"),
    'type' => getenv("MB_TRANSACTIONAL_EXCHANGE_TYPE"),
    'passive' => getenv("MB_TRANSACTIONAL_EXCHANGE_PASSIVE"),
    'durable' => getenv("MB_TRANSACTIONAL_EXCHANGE_DURABLE"),
    'auto_delete' => getenv("MB_TRANSACTIONAL_EXCHANGE_AUTO_DELETE"),
  ),
  'queue' => array(
    'userAPICampaignActivity' => array(
      'name' => getenv("MB_USER_API_CAMPAIGN_ACTIVITY_QUEUE"),
      'passive' => getenv("MB_USER_API_CAMPAIGN_ACTIVITY_QUEUE_PASSIVE"),
      'durable' => getenv("MB_USER_API_CAMPAIGN_ACTIVITY_QUEUE_DURABLE"),
      'exclusive' => getenv("MB_USER_API_CAMPAIGN_ACTIVITY_QUEUE_EXCLUSIVE"),
      'auto_delete' => getenv("MB_USER_API_CAMPAIGN_ACTIVITY_QUEUE_AUTO_DELETE"),
      'bindingKey' => getenv("MB_USER_API_CAMPAIGN_ACTIVITY_QUEUE_TOPIC_MB_TRANSACTIONAL_EXCHANGE_PATTERN"),
    ),
  ),
  'routingKey' => getenv("MB_USER_API_CAMPAIGN_ACTIVITY_ROUTING_KEY"),
);

$settings = array(
  'stathat_ez_key' => getenv("STATHAT_EZKEY"),
  'use_stathat_tracking' => getenv('USE_STAT_TRACKING'),
  'ds_user_api_host' => getenv('DS_USER_API_HOST'),
  'ds_user_api_port' => getenv('DS_USER_API_PORT'),
);


// Kick off
$mb = new MessageBroker($credentials, $config);
$mb->consumeMessage(array(new MBC_UserAPICampaignActivity($mb, $settings), 'updateUserAPI'));
