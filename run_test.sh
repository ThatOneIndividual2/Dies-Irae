#!/bin/bash
cd /var/www/html/DiesIrae
./vendor/bin/phpunit --filter test_campaign_choice_that_nudges_a_goal_to_full_marks_it_completed tests/Feature/Campaign1347/Europa1347CampaignTest.php > /tmp/phpunit_stdout.txt 2> /tmp/phpunit_stderr.txt
echo $? > /tmp/phpunit_exit.txt
