<?php

namespace IPP\Student;

use DOMDocument;
use IPP\Core\AbstractInterpreter;
use IPP\Core\Exception\NotImplementedException;
use IPP\Core\Exception\XMLException;
use ValueError;

class Instruction
{
    public function init (): void
    {

    }
}

class InstructionList
{
    /** @var array<Instruction> */
    public array $list;

    public function init(DOMDocument $dom): void
    {
        return;
    }
}

class Interpreter extends AbstractInterpreter
{
    public function execute(): int
    {
        // TODO: Start your code here
        // Check \IPP\Core\AbstractInterpreter for predefined I/O objects:

        try
        {
            $dom = $this->source->getDOMDocument();
        }

        /* this exception is uncaught elsewhere and it happens when the source
        file is empty */
        catch (ValueError) {
            throw new XMLException("empty xml");
        }

        // $val = $this->input->readString();
        // $this->stdout->writeString("stdout");
        // $this->stderr->writeString("stderr");
        $this->stdout->writeString("ahoj svete\n");
        $k = new InstructionList;
        return 0;
        // throw new NotImplementedException;
    }
}
