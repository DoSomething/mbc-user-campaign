<?php
/**
 * mbc-user-campaign.php
 *
 * Collect user campaign activity from the userAPICampaignActivityQueue. Update the
 * UserAPI / database with user campaign activity.
 */

date_default_timezone_set('America/New_York');

define('CONFIG_PATH',  __DIR__ . '/messagebroker-config');
// The number of messages for the consumer to reserve with each callback
// See consumeMwessage for further details.
// Necessary for parallel processing when more than one consumer is running on the same queue.
define('QOS_SIZE', 1);

// Load up the Composer autoload magic
require_once __DIR__ . '/vendor/autoload.php';
use DoSomething\MBC_UserAPI_CampaignActivity\MBC_UserAPI_CampaignActivity_Consumer;

require_once __DIR__ . '/mbc-userAPI-campaignActivity.config.inc';

// Kick off
echo '------- mbc-userAPI-campaignActivity START: ' . date('D M j G:i:s T Y') . ' -------', PHP_EOL;

$mb = $mbConfig->getProperty('messageBroker');
$mb->consumeMessage(array(new MBC_UserAPI_CampaignActivity_Consumer(), 'consumeUserAPICampaignActivityQueue'), QOS_SIZE);

echo '------- mbc-userAPI-campaignActivity END: ' . date('D M j G:i:s T Y') . ' -------', PHP_EOL;