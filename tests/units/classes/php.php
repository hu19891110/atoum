<?php

namespace mageekguy\atoum\tests\units;

use
	mageekguy\atoum,
	mageekguy\atoum\php as testedClass
;

require_once __DIR__ . '/../runner.php';

class php extends atoum\test
{
	public function test__construct()
	{
		$this
			->if($php = new testedClass())
			->then
				->string($php->getBinaryPath())->isNotEmpty()
				->object($php->getAdapter())->isEqualTo(new atoum\adapter())
				->string($php->getStdout())->isEmpty()
				->string($php->getStderr())->isEmpty()
				->array($php->getOptions())->isEmpty()
			->if($php = new testedClass($phpPath = uniqid(), $adapter = new atoum\adapter()))
			->then
				->string($php->getBinaryPath())->isEqualTo($phpPath)
				->object($php->getAdapter())->isIdenticalTo($adapter)
				->string($php->getStdout())->isEmpty()
				->string($php->getStderr())->isEmpty()
				->array($php->getOptions())->isEmpty()
		;
	}

	public function test__toString()
	{
		$this
			->if($php = new testedClass())
			->then
				->castToString($php)->isEqualTo($php->getBinaryPath())
		;
	}

	public function testSetAdapter()
	{
		$this
			->given($php = new testedClass())
			->then
				->object($php->setAdapter($adapter = new atoum\adapter()))->isIdenticalTo($php)
				->object($php->getAdapter())->isIdenticalTo($adapter)
				->object($php->setAdapter())->isIdenticalTo($php)
				->object($php->getAdapter())
					->isNotIdenticalTo($adapter)
					->isEqualTo(new atoum\adapter())
		;
	}

	public function testSetBinaryPath()
	{
		$this
			->given($php = new atoum\php(null, $adapter = new atoum\test\adapter()))
			->if($adapter->defined = function($constant) { return ($constant == 'PHP_BINARY'); })
			->and($adapter->constant = function($constant) use (& $phpBinary) { return ($constant != 'PHP_BINARY' ? null : $phpBinary = uniqid()); })
			->then
				->object($php->setBinaryPath())->isIdenticalTo($php)
				->string($php->getBinaryPath())->isEqualTo($phpBinary)
			->if($adapter->defined = false)
			->and($adapter->constant = null)
			->and($adapter->getenv = function($variable) use (& $pearBinaryPath) { return ($variable != 'PHP_PEAR_PHP_BIN' ? false : $pearBinaryPath = uniqid()); })
			->then
				->object($php->setBinaryPath())->isIdenticalTo($php)
				->string($php->getBinaryPath())->isEqualTo($pearBinaryPath)
			->if($adapter->getenv = function($variable) use (& $phpBinPath) {
					switch ($variable)
					{
						case 'PHPBIN':
							return ($phpBinPath = uniqid());

						default:
							return false;
					}
				}
			)
			->then
				->object($php->setBinaryPath())->isIdenticalTo($php)
				->string($php->getBinaryPath())->isEqualTo($phpBinPath)
			->if($adapter->constant = function($constant) use (& $phpBinDir) { return ($constant != 'PHP_BINDIR' ? null : $phpBinDir = uniqid()); })
			->and($adapter->getenv = false)
			->then
				->object($php->setBinaryPath())->isIdenticalTo($php)
				->string($php->getBinaryPath())->isEqualTo($phpBinDir . '/php')
				->object($php->setBinaryPath($phpPath = uniqid()))->isIdenticalTo($php)
				->string($php->getBinaryPath())->isEqualTo($phpPath)
		;
	}

	public function testAddOption()
	{
		$this
			->if($php = new testedClass())
			->then
				->object($php->addOption($optionName = uniqid()))->isIdenticalTo($php)
				->array($php->getOptions())->isEqualTo(array($optionName => null))
				->object($php->addOption($optionName))->isIdenticalTo($php)
				->array($php->getOptions())->isEqualTo(array($optionName => null))
				->object($php->addOption($otherOptionName = uniqid()))->isIdenticalTo($php)
				->array($php->getOptions())->isEqualTo(array($optionName => null, $otherOptionName => null))
				->object($php->addOption($anotherOptionName = uniqid(), $optionValue = uniqid()))->isIdenticalTo($php)
				->array($php->getOptions())->isEqualTo(array($optionName => null, $otherOptionName => null, $anotherOptionName => $optionValue))
				->object($php->addOption($anotherOptionName, $anotherOptionValue = uniqid()))->isIdenticalTo($php)
				->array($php->getOptions())->isEqualTo(array($optionName => null, $otherOptionName => null, $anotherOptionName => $anotherOptionValue))
		;
	}

	public function testExecute()
	{
		$this
			->if($php = new testedClass($phpPath = uniqid(), $adapter = new atoum\test\adapter()))
			->and($adapter->proc_open = false)
			->then
				->exception(function() use ($php, & $code) { $php->execute($code = uniqid()); })
					->isInstanceOf('mageekguy\atoum\php\exception')
					->hasMessage('Unable to execute \'' . $code . '\' with php binary \'' . $phpPath . '\'')
				->adapter($adapter)
					->call('proc_open')->withArguments(escapeshellarg($phpPath), array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w')), array())->once()
			->if($php = new testedClass($phpPath = uniqid(), $adapter))
			->and($code = uniqid())
			->and($adapter->proc_open = function($command, $descriptors, & $streams) use (& $phpResource, & $stdin, & $stdout, & $stderr) { $streams = array($stdin = uniqid(), $stdout = uniqid(), $stderr = uniqid); return ($phpResource = uniqid()); })
			->and($adapter->fwrite = strlen($code))
			->and($adapter->fclose = null)
			->and($adapter->stream_set_blocking = null)
			->then
				->object($php->execute($code))->isIdenticalTo($php)
				->adapter($adapter)
					->call('proc_open')->withArguments(escapeshellarg($phpPath), array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w')), array())->once()
					->call('fwrite')->withArguments($stdin, $code, strlen($code))->once()
					->call('fclose')->withArguments($stdin)->once()
					->call('stream_set_blocking')->withArguments($stdout)->once()
					->call('stream_set_blocking')->withArguments($stderr)->once()
			->if($php = new testedClass($phpPath = uniqid(), $adapter))
			->and($adapter->resetCalls())
			->and($adapter->fwrite[1] = 4)
			->and($adapter->fwrite[2] = strlen($code) - 4)
			->then
				->object($php->execute($code))->isIdenticalTo($php)
				->adapter($adapter)
					->call('proc_open')->withArguments(escapeshellarg($phpPath), array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w')), array())->once()
					->call('fwrite')->withArguments($stdin, $code, strlen($code))->once()
					->call('fwrite')->withArguments($stdin, substr($code, 4), strlen($code) - 4)->once()
					->call('fclose')->withArguments($stdin)->once()
					->call('stream_set_blocking')->withArguments($stdout)->once()
					->call('stream_set_blocking')->withArguments($stderr)->once()
			->if($php = new testedClass($phpPath = uniqid(), $adapter))
			->and($php->addOption('firstOption'))
			->then
				->object($php->execute($code))->isIdenticalTo($php)
				->adapter($adapter)
					->call('proc_open')->withArguments(escapeshellarg($phpPath) . ' firstOption', array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w')), array())->once()
			->if($php = new testedClass($phpPath = uniqid(), $adapter))
			->and($php->addOption('firstOption'))
			->and($php->addOption('secondOption', 'secondOptionValue'))
				->object($php->execute($code))->isIdenticalTo($php)
				->adapter($adapter)
					->call('proc_open')->withArguments(escapeshellarg($phpPath) . ' firstOption secondOption ' . escapeshellarg('secondOptionValue'), array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w')), array())->once()
		;
	}
}
