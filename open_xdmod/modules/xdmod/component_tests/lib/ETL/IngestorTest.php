<?php
/**
 * @package OpenXdmod\ComponentTests
 * @author Steven M. Gallo <smgallo@buffalo.edu>
 */

namespace ComponentTests\ETL;

use CCR\DB;

/**
 * @group OpenXDMoD
 * Test various components of the ETLv2 ingestors.
 * @group OpenXDMoD
 */

class IngestorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Load invalid data and ensure that LOAD DATA INFILE returns appropriate warning messages.
     */

    public function testLoadDataInfileWarnings() {
        $result = $this->executeOverseerAction('test-load-data-infile-warnings');

        $this->assertEquals(0, $result['exit_status']);

        $numWarnings = 0;
        foreach ( explode(PHP_EOL, trim($result['stdout'])) as $line ) {
            $this->assertRegExp('/\[warning\]/', $line);
            $numWarnings++;
        }

        $this->assertEquals(3, $numWarnings);
        $this->assertEquals('', $result['stderr']);
    }

    /**
     * Execute the ETL overseer.
     *
     * @param string $pipeline The name of the pipeline to execute
     */

    private function executeOverseerAction($action)
    {
        // Note that tests are run in the directory where the PHP class is defined.
        $overseer = realpath(__DIR__ . '/../../../../../../tools/etl/etl_overseer.php');
        $configFile = realpath(__DIR__ . '/../../artifacts/xdmod-test-artifacts/xdmod/etlv2/configuration/input/xdmod_etl_config.json');
        $options = sprintf('-c %s -a %s -v warning', $configFile, $action);
        $pipes = array();

        $process = proc_open(
            sprintf('%s %s', $overseer, $options),
            array(
                0 => array('file', '/dev/null', 'r'),  // STDIN
                1 => array('pipe', 'w'),               // STDOUT
                2 => array('pipe', 'w'),               // STDERR
            ),
            $pipes
        );

        if ( ! is_resource($process) ) {
            throw new Exception(sprintf('Failed to create %s subprocess', $command));
        }

        $stdout = stream_get_contents($pipes[1]);

        if ( false === $stdout ) {
            throw new Execption('Failed to get subprocess STDOUT');
        }

        $stderr = stream_get_contents($pipes[2]);

        if (false === $stderr) {
            throw new Execption('Failed to get subprocess STDERR');
        }

        $exitStatus = proc_close($process);

        return array(
            'exit_status' => $exitStatus,
            'stdout' => $stdout,
            'stderr' => $stderr,
        );
    }

    /**
     * Clean up tables created during the tests
     *
     * @return Nothing
     */

    public static function tearDownAfterClass()
    {
        $dbh = DB::factory('database');
        $dbh->execute('DROP TABLE IF EXISTS `test`.`load_data_infile_test`');
    }
}
