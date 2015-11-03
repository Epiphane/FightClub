# FightClub
Slack-based fight interface (http://thomassteinke.com/api/fight

### How to use it

First, create a `config.php` file:

```
<?

define("FIGHT_DB_HOST", "...");
define("FIGHT_DB_USER", "...");
define("FIGHT_DB_PASS", "...");
define("FIGHT_DB_NAME", "...");
```

Then, in that database, create the following tables:

```
CREATE TABLE `fight` (
 `fight_id` int(11) NOT NULL AUTO_INCREMENT,
 `channel_id` varchar(16) NOT NULL,
 `user_id` int(11) NOT NULL,
 `status` varchar(8) NOT NULL DEFAULT 'progress',
 `health` int(11) NOT NULL DEFAULT '100',
 `deleted` int(1) NOT NULL DEFAULT '0',
 PRIMARY KEY (`fight_id`,`channel_id`,`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=995192417 DEFAULT CHARSET=latin1
CREATE TABLE `fight_action` (
 `fight_id` int(11) NOT NULL,
 `action_id` int(11) NOT NULL,
 `description` text NOT NULL,
 `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 `actor_id` int(11) NOT NULL,
 PRIMARY KEY (`fight_id`,`action_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1
CREATE TABLE `fight_app` (
 `team` varchar(64) NOT NULL,
 `api_token` varchar(256) NOT NULL,
 `channel` varchar(64) NOT NULL,
 PRIMARY KEY (`team`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1
fight_item	CREATE TABLE `fight_item` (
 `item_id` int(11) NOT NULL,
 `user_id` int(11) NOT NULL,
 `name` varchar(128) NOT NULL,
 `stats` text NOT NULL,
 `type` varchar(4) NOT NULL,
 `deleted` int(1) NOT NULL DEFAULT '0',
 PRIMARY KEY (`item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1
CREATE TABLE `fight_prefs` (
 `channel_id` varchar(16) NOT NULL,
 `reactions` int(1) NOT NULL DEFAULT '1',
 `api_token` varchar(64) DEFAULT NULL,
 PRIMARY KEY (`channel_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1
CREATE TABLE `fight_reaction` (
 `image_id` int(11) NOT NULL,
 `type` varchar(16) NOT NULL,
 `image_url` varchar(128) NOT NULL,
 `user_id` int(11) NOT NULL,
 PRIMARY KEY (`image_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1
CREATE TABLE `fight_user` (
 `user_id` int(11) NOT NULL,
 `slack_name` varchar(16) NOT NULL,
 `team_id` varchar(16) NOT NULL,
 `AI` int(1) NOT NULL DEFAULT '0',
 `name` varchar(32) NOT NULL,
 `level` int(11) NOT NULL DEFAULT '1',
 `experience` int(11) NOT NULL DEFAULT '0',
 `weapon` int(11) DEFAULT NULL,
 `armor` int(11) DEFAULT NULL,
 PRIMARY KEY (`user_id`),
 UNIQUE KEY `team_id` (`team_id`,`name`),
 UNIQUE KEY `team_id_2` (`team_id`,`name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1
```

Finally, assuming you want to incorporate it with Slack, just do the following:

```
require_once "path/to/SlackWrapper.php";

use Fight\SlackWrapper;

SlackWrapper::respond();
```

It's thaaaat easy! I think.
