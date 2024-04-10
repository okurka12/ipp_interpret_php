<?php

namespace IPP\Student;

use DOMDocument;
use DOMElement;
use IPP\Core\AbstractInterpreter;
use IPP\Core\Exception\NotImplementedException;
use IPP\Core\Exception\XMLException;
use Stringable;
use ValueError;

/* debug print to stderr, dont call this directly, use dprintinfo or dprint */
function dprint_stderr(string $s): void
{
    /* comment this out before submission */

    file_put_contents('php://stderr', $s);
    /* https://stackoverflow.com/questions/6079492/how-to-print-a-debug-log */
}

/* prints information about `v` (uses print_r) prefixed with `s` */
function dprintinfo(string $s, mixed $v): void
{
    dprint_stderr($s . ": " . print_r($v, TRUE) . "\n");
}

/* prints string version of `v` prefixed with `s` */
function dprintstring(string $s, mixed $v): void
{
    if ($v instanceof Stringable || is_string($v)) {
        dprint_stderr($s . ": " . (string)$v . "\n");
    } else {
        if (is_object(($v)))
        {
            $objinfo = "(object of class " . get_class($v) . ")";
        }
        else
        {
            $objinfo = "";
        }
        dprint_stderr("warning: dprintstring called on non-stringable " .
        "variable" . $objinfo . "\n");
    }
}

class Instruction
{
    /* raw dom element */
    private DOMElement $raw_de;

    /* instruction opcode, always lowercase */
    private string $opcode;

    private int $order;
    public function __construct(DOMElement $de)
    {
        $this->raw_de = $de;
        $this->opcode = strtolower($de->getAttribute("opcode"));
        $this->order = (int)$de->getAttribute("order");
    }

    public function __toString()
    {
        return "opcode: " . str_pad($this->opcode, 10) . " order: " .
        (string)$this->order;
    }
    public function is_label(): bool
    {
        return $this->opcode === "label";
    }
    public function get_content(): string
    {
        /* the label is actually in the child `arg1` element but thats okay */
        return $this->raw_de->textContent;
    }
}

class InstructionList
{
    /** @var array<Instruction> */
    public array $list;

    public function __construct(DOMDocument $dom)
    {
        $this->list = array();
        foreach($dom->getElementsByTagName("instruction") as $inele) {
            array_push($this->list, new Instruction($inele));
            dprintstring("instruction", end($this->list));
        }
        return;
    }

    /** @return array<string> */
    public function get_labels(): array
    {
        $output = array();
        foreach ($this->list as $in)
        {
            if ($in->is_label())
            {
                array_push($output, $in->get_content());
            }
        }
        return $output;
    }

}

class Interpreter extends AbstractInterpreter
{
    public function execute(): int
    {
        // TODO: Start your code here
        // Check \IPP\Core\AbstractInterpreter for predefined I/O objects:

        try  // catch ValueError
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

        $k = new InstructionList($dom);
        $labels = $k->get_labels();
        dprintinfo("labels", $labels);
        return 0;
        // throw new NotImplementedException;
    }
}
