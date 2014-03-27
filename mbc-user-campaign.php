<?php
/**
 * mbc-user-campaign.php
 *
 * Collect user campaign activity from the userCampaignActivityQueue. Update the
 * UserAPI / database with user activity.
 */

// Load up the Composer autoload magic
require_once __DIR__ . '/vendor/autoload.php';

// Load configuration settings common to the Message Broker system
// symlinks in the project directory point to the actual location of the files
require __DIR__ . '/mb-secure-config.inc';
require __DIR__ . '/mb-config.inc';

class MBC_UserCampaignActivity
{
  
  /**
   * Message Broker object that details the connection to RabbitMQ.
   *
   * @var object
   */
  private $MessageBroker;
  
  /**
   * Constructor for MBC_UserCampaignActivity
   *
   * @param array $credentials
   *   Secret settings from mb-secure-config.inc
   *
   * @param array $config
   *   Configuration settings from mb-config.inc
   */
  public function __construct($credentials, $config) {

    $this->config = $config;
    $this->credentials = $credentials;

    // Setup RabbitMQ connection
    $this->MessageBroker = new MessageBroker($credentials, $config);
    $connection = $this->MessageBroker->connection;
    $this->channel = $connection->channel();
    
    // Exchange
    $this->channel = $this->MessageBroker->setupExchange($config['exchange']['name'], $config['exchange']['type'], $this->channel);

    // Queue - userCampaignActivityQueue
    list($this->channel, ) = $this->MessageBroker->setupQueue($config['queue']['userCampaignActivity']['name'], $this->channel);

    // Queue binding
    $this->channel->queue_bind($config['queue']['userCampaignActivity']['name'], $config['exchange']['name'], $config['queue']['userCampaignActivity']['bindingKey']);

  }

}

// Settings
$credentials = array(
  'host' =>  getenv("RABBITMQ_HOST"),
  'port' => getenv("RABBITMQ_PORT"),
  'username' => getenv("RABBITMQ_USERNAME"),
  'password' => getenv("RABBITMQ_PASSWORD"),
  'vhost' => getenv("RABBITMQ_VHOST"),
);
$credentials['mailchimp_apikey'] = getenv("MAILCHIMP_APIKEY");

$config = array(
  'exchange' => array(
    'name' => getenv("MB_TRANSACTIONAL_EXCHANGE"),
    'type' => getenv("MB_TRANSACTIONAL_EXCHANGE_TYPE"),
    'passive' => getenv("MB_TRANSACTIONAL_EXCHANGE_PASSIVE"),
    'durable' => getenv("MB_TRANSACTIONAL_EXCHANGE_DURABLE"),
    'auto_delete' => getenv("MB_TRANSACTIONAL_EXCHANGE_AUTO_DELETE"),
  ),
  'queue' => array(
    'userCampaignActivity' => array(
      'name' => getenv("MB_USER_CAMPAIGN_ACTIVTY_QUEUE"),
      'passive' => getenv("MB_USER_CAMPAIGN_ACTIVTY_QUEUE_PASSIVE"),
      'durable' => getenv("MB_USER_CAMPAIGN_ACTIVTY_QUEUE_DURABLE"),
      'exclusive' => getenv("MB_USER_CAMPAIGN_ACTIVTY_QUEUE_EXCLUSIVE"),
      'auto_delete' => getenv("MB_USER_CAMPAIGN_ACTIVTY_QUEUE_AUTO_DELETE"),
      'bindingKey' => getenv("MB_USER_CAMPAIGN_ACTIVTY_QUEUE_TOPIC_MB_TRANSACTIONAL_EXCHANGE_PATTERN"),
    ),
  ),
);

// Kick off
$mbcUserRegistration = new MBC_UserCampaignActivity($credentials, $config);