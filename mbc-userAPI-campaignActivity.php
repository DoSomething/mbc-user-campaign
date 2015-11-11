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
use DoSomething\MB_Toolbox\MB_Configuration;

// Load configuration settings common to the Message Broker system
// symlinks in the project directory point to the actual location of the files
require_once __DIR__ . '/messagebroker-config/mb-secure-config.inc';
require_once __DIR__ . '/MBC_UserAPICampaignActivity.class.inc';

// Settings
$credentials = array(
  'host' =>  getenv("RABBITMQ_HOST"),
  'port' => getenv("RABBITMQ_PORT"),
  'username' => getenv("RABBITMQ_USERNAME"),
  'password' => getenv("RABBITMQ_PASSWORD"),
  'vhost' => getenv("RABBITMQ_VHOST"),
);

$settings = array(
  'stathat_ez_key' => getenv("STATHAT_EZKEY"),
  'use_stathat_tracking' => getenv('USE_STAT_TRACKING'),
  'ds_user_api_host' => getenv('DS_USER_API_HOST'),
  'ds_user_api_port' => getenv('DS_USER_API_PORT'),
);

$config = array();
$source = __DIR__ . '/messagebroker-config/mb_config.json';
$mb_config = new MB_Configuration($source, $settings);
$transactionalExchange = $mb_config->exchangeSettings('transactionalExchange');

$config['exchange'] = array(
  'name' => $transactionalExchange->name,
  'type' => $transactionalExchange->type,
  'passive' => $transactionalExchange->passive,
  'durable' => $transactionalExchange->durable,
  'auto_delete' => $transactionalExchange->auto_delete,
);
foreach ($transactionalExchange->queues->userAPICampaignActivityQueue->binding_patterns as $binding_key) {
  $config['queue'][] = array(
    'name' => $transactionalExchange->queues->userAPICampaignActivityQueue->name,
    'passive' => $transactionalExchange->queues->userAPICampaignActivityQueue->passive,
    'durable' =>  $transactionalExchange->queues->userAPICampaignActivityQueue->durable,
    'exclusive' =>  $transactionalExchange->queues->userAPICampaignActivityQueue->exclusive,
    'auto_delete' =>  $transactionalExchange->queues->userAPICampaignActivityQueue->auto_delete,
    'bindingKey' => $binding_key,
  );
}


// Kick off
$mb = new MessageBroker($credentials, $config);
$mb->consumeMessage(array(new MBC_UserAPICampaignActivity($mb, $settings), 'updateUserAPI'));
