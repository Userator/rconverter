<?php

namespace Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Exception;

/**
 * @author kurshin
 */
class Convert extends Command {

	protected function configure() {
		$this->setName('convert');
		$this->setDescription('Конвертировать');
		$this->setHelp('Конвертировать' . PHP_EOL . 'Usage: <info>php app/cli.php ' . $this->getName() . '</info>');
		$this->setDefinition([
			new InputArgument('name', InputArgument::REQUIRED, 'название теста'),
			new InputArgument('shortname', InputArgument::REQUIRED, 'короткое название теста'),
			new InputArgument('threshold', InputArgument::REQUIRED, 'процент правильных ответов'),
			new InputArgument('limit', InputArgument::REQUIRED, 'количество вопросов'),
			new InputArgument('file', InputArgument::REQUIRED, 'путь до файла'),
		]);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		if (!file_exists($input->getArgument('file'))) throw new Exception\InvalidArgumentException('некорректный аргумент "file"');

		try {
			$rows = $this->parseRows($input->getArgument('file'));
			$struct = $this->makeStruct($rows, $input->getArgument('name'), $input->getArgument('shortname'), $input->getArgument('threshold'), $input->getArgument('limit'));
			$render = $this->render($struct);

//			$output->write(print_r($struct, 1));
			$output->write($render);
			return 0;
		} catch (\Exception $e) {
			$output->writeln('<error>' . $e->getMessage() . '</error>');
			return 255;
		}
	}

	protected function parseRows($file) {
		$reader = \Box\Spout\Reader\ReaderFactory::create(\Box\Spout\Common\Type::XLSX);
		$reader->open($file);

		$output = [];
		foreach ($reader->getSheetIterator() as $sheetidx => $sheet) {
			if ($sheetidx !== 1) continue;
			foreach ($sheet->getRowIterator() as $rowidx => $row) {
				if ($rowidx == 1) continue;
				$output[$rowidx] = $row;
			}
		}

		$reader->close();

		return $output;
	}

	protected function makeStruct(array $rows, $name, $shortname, $threshold, $limit) {
		$inquiry = [
			'name' => $name,
			'shortname' => $shortname,
			'threshold' => $threshold,
			'limit' => $limit,
			'questions' => [],
		];

		$idx = 0;
		foreach ($rows as $key => $row) {
			if (strlen($row[1])) {
				$idx++;
				$inquiry['questions'][$idx]['text'] = $row[1];
				$inquiry['questions'][$idx]['picture'] = 'None';
				$inquiry['questions'][$idx]['annotation'] = $row[5];
				$inquiry['questions'][$idx]['answers'][trim($row[2])] = $row[3];
				$inquiry['questions'][$idx]['trues'] = array_map('trim', explode(',', $row[4]));
			} else {
				$inquiry['questions'][$idx]['answers'][trim($row[2])] = $row[3];
			}
		}

		return $inquiry;
	}

	protected function render(array $struct) {
		$output = '';
		$output .= 'Name' . PHP_EOL;
		$output .= $struct['name'] . PHP_EOL;
		$output .= 'Short_name' . PHP_EOL;
		$output .= $struct['shortname'] . PHP_EOL;
		$output .= 'Threshold' . PHP_EOL;
		$output .= $struct['threshold'] . PHP_EOL;
		$output .= 'Questions_in_the_test' . PHP_EOL;
		$output .= $struct['limit'] . PHP_EOL;
		$output .= 'Questions' . PHP_EOL;
		$output .= PHP_EOL;

		foreach ($struct['questions'] as $question) {
			$output .= 'Text' . PHP_EOL;
			$output .= $question['text'] . PHP_EOL;
			$output .= 'Picture' . PHP_EOL;
			$output .= $question['picture'] . PHP_EOL;
			$output .= 'Annotaion' . PHP_EOL;
			$output .= $question['annotation'] . PHP_EOL;
			foreach ($question['answers'] as $idx => $answer) {
				$output .= 'Ans ' . $idx . ((in_array($idx, $question['trues'])) ? ' R' : '') . PHP_EOL;
				$output .= $answer . PHP_EOL;
			}

			$output .= PHP_EOL;
		}

		return $output;
	}

}