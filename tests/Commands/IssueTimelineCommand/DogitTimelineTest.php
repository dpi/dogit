<?php

declare(strict_types=1);

namespace dogit\tests\Commands\IssueTimelineCommand;

use dogit\Commands\IssueTimelineCommand;
use dogit\tests\DogitGuzzleTestMiddleware;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @coversDefaultClass \dogit\Commands\IssueTimelineCommand
 */
final class DogitTimelineTest extends TestCase
{
    /**
     * @covers ::execute
     */
    public function testTimeline(): void
    {
        $command = new IssueTimelineCommand();
        $command->handlerStack()->push(new DogitGuzzleTestMiddleware());
        $tester = new CommandTester($command);
        $result = $tester->execute(['issue-id' => '123456789']);
        $this->assertEquals(0, $result);
        $this->assertEquals(<<<OUTPUT
            
            Comment #13370001 (1) at Tue, 13 May 2014 19:40:00 +0000
            ========================================================
            
             🗣 Comment by 👤 larowlan on Tue, 13 May 2014 19:40:00 +0000
             Status change from Needs work to Needs review
             🧪 Test result: 8.2.x: ✅ PHP 7.1 & MySQL 5.7 26,400 pass
            
            Comment #13370002 (2) at Tue, 13 May 2014 22:26:40 +0000
            ========================================================
            
             🗣 Comment by 👤 larowlan on Tue, 13 May 2014 22:26:40 +0000
             Version changed from 8.2.x to 8.3.x
            
            Comment #13370003 (3) at Wed, 14 May 2014 01:13:20 +0000
            ========================================================
            
             🗣 Comment by 👤 larowlan on Wed, 14 May 2014 01:13:20 +0000
            
            Comment #13370004 (4) at Wed, 14 May 2014 04:00:00 +0000
            ========================================================
            
             🗣 Comment by 👤 larowlan on Wed, 14 May 2014 04:00:00 +0000
            
            Comment #13370005 (5) at Wed, 14 May 2014 06:46:40 +0000
            ========================================================
            
             🗣 Comment by 👤 larowlan on Wed, 14 May 2014 06:46:40 +0000
             🧪 Test result: 8.3.x: ✅ PHP 7.1 & MySQL 5.7 26,400 pass
            
            Comment #13370006 (6) at Wed, 14 May 2014 06:46:40 +0000
            ========================================================
            
             🗣 Comment by 👤 larowlan on Wed, 14 May 2014 06:46:40 +0000
             Version changed from 8.3.x to 8.4.x
            
            Comment #13370007 (7) at Wed, 14 May 2014 06:46:40 +0000
            ========================================================
            
             🗣 Comment by 👤 larowlan on Wed, 14 May 2014 06:46:40 +0000
             🧪 Test result: 8.4.x: ✅ PHP 7.1 & MySQL 5.7 26,400 pass
            
            Comment #13370008 (8) at Wed, 14 May 2014 15:06:40 +0000
            ========================================================
            
             🗣 Comment by 👤 larowlan on Wed, 14 May 2014 15:06:40 +0000
             Version changed from 8.4.x to 8.5.x

            Comment #13370009 (9) at Wed, 14 May 2014 15:06:40 +0000
            ========================================================
            
             🗣 Comment by 🤖 System Message on Wed, 14 May 2014 15:06:40 +0000
             Merge request !333 created: https://git.drupalcode.org/project/drupal/-/merge_requests/333
            
            OUTPUT, $tester->getDisplay());
    }

    /**
     * @covers ::execute
     */
    public function testTimelineNoComments(): void
    {
        $command = new IssueTimelineCommand();
        $command->handlerStack()->push(new DogitGuzzleTestMiddleware());
        $tester = new CommandTester($command);
        $result = $tester->execute([
            'issue-id' => '123456789',
            '--no-comments' => true,
        ]);
        $this->assertEquals(0, $result);
        $this->assertEquals(<<<OUTPUT
            
            Comment #13370001 (1) at Tue, 13 May 2014 19:40:00 +0000
            ========================================================
            
             Status change from Needs work to Needs review
             🧪 Test result: 8.2.x: ✅ PHP 7.1 & MySQL 5.7 26,400 pass
            
            Comment #13370002 (2) at Tue, 13 May 2014 22:26:40 +0000
            ========================================================
            
             Version changed from 8.2.x to 8.3.x
            
            Comment #13370005 (5) at Wed, 14 May 2014 06:46:40 +0000
            ========================================================
            
             🧪 Test result: 8.3.x: ✅ PHP 7.1 & MySQL 5.7 26,400 pass
            
            Comment #13370006 (6) at Wed, 14 May 2014 06:46:40 +0000
            ========================================================
            
             Version changed from 8.3.x to 8.4.x
            
            Comment #13370007 (7) at Wed, 14 May 2014 06:46:40 +0000
            ========================================================
            
             🧪 Test result: 8.4.x: ✅ PHP 7.1 & MySQL 5.7 26,400 pass
            
            Comment #13370008 (8) at Wed, 14 May 2014 15:06:40 +0000
            ========================================================
            
             Version changed from 8.4.x to 8.5.x

            Comment #13370009 (9) at Wed, 14 May 2014 15:06:40 +0000
            ========================================================
            
             Merge request !333 created: https://git.drupalcode.org/project/drupal/-/merge_requests/333
            
            OUTPUT, $tester->getDisplay());
    }

    /**
     * @covers ::execute
     */
    public function testTimelineNoEvents(): void
    {
        $command = new IssueTimelineCommand();
        $command->handlerStack()->push(new DogitGuzzleTestMiddleware());
        $tester = new CommandTester($command);
        $result = $tester->execute([
            'issue-id' => '123456789',
            '--no-events' => true,
        ]);
        $this->assertEquals(0, $result);
        $this->assertEquals(<<<OUTPUT
            
            Comment #13370001 (1) at Tue, 13 May 2014 19:40:00 +0000
            ========================================================
            
             🗣 Comment by 👤 larowlan on Tue, 13 May 2014 19:40:00 +0000
            
            Comment #13370002 (2) at Tue, 13 May 2014 22:26:40 +0000
            ========================================================
            
             🗣 Comment by 👤 larowlan on Tue, 13 May 2014 22:26:40 +0000
            
            Comment #13370003 (3) at Wed, 14 May 2014 01:13:20 +0000
            ========================================================
            
             🗣 Comment by 👤 larowlan on Wed, 14 May 2014 01:13:20 +0000
            
            Comment #13370004 (4) at Wed, 14 May 2014 04:00:00 +0000
            ========================================================
            
             🗣 Comment by 👤 larowlan on Wed, 14 May 2014 04:00:00 +0000
            
            Comment #13370005 (5) at Wed, 14 May 2014 06:46:40 +0000
            ========================================================
            
             🗣 Comment by 👤 larowlan on Wed, 14 May 2014 06:46:40 +0000
            
            Comment #13370006 (6) at Wed, 14 May 2014 06:46:40 +0000
            ========================================================
            
             🗣 Comment by 👤 larowlan on Wed, 14 May 2014 06:46:40 +0000
            
            Comment #13370007 (7) at Wed, 14 May 2014 06:46:40 +0000
            ========================================================
            
             🗣 Comment by 👤 larowlan on Wed, 14 May 2014 06:46:40 +0000
            
            Comment #13370008 (8) at Wed, 14 May 2014 15:06:40 +0000
            ========================================================
            
             🗣 Comment by 👤 larowlan on Wed, 14 May 2014 15:06:40 +0000

            Comment #13370009 (9) at Wed, 14 May 2014 15:06:40 +0000
            ========================================================
            
             🗣 Comment by 🤖 System Message on Wed, 14 May 2014 15:06:40 +0000
            
            OUTPUT, $tester->getDisplay());
    }
}
