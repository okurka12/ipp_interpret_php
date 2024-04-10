<?php

namespace IPP\Student;

use DOMDocument;
use DOMElement;
use IPP\Core\AbstractInterpreter;
use IPP\Core\Exception\InternalErrorException;
use IPP\Core\Exception\IPPException;
use IPP\Core\Exception\NotImplementedException;
use IPP\Core\Exception\XMLException;
use IPP\Core\ReturnCode;
use Stringable;
use ValueError;

/* FUNCTIONS */
/******************************************************************************/

/* debug print to stderr, dont call this directly, use dprintinfo or dprint
or dlog */
function dprint_stderr(string $s): void
{
    /* comment this out before submission */

    file_put_contents('php://stderr', $s);
    /* https://stackoverflow.com/questions/6079492/how-to-print-a-debug-log */
}

/* prints information about `v` (uses print_r) prefixed with `s` to stderr */
function dprintinfo(string $s, mixed $v): void
{
    dprint_stderr($s . ": " . print_r($v, TRUE) . "\n");
}

/* prints string version of `v` prefixed with `s` to stderr */
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

/* prints `s` to stderr */
function dlog(string $s): void
{
    dprint_stderr($s);
}

/* CLASSES */
/******************************************************************************/

class UnknownLabelError extends IPPException
{
    public function __construct()
    {
        parent::__construct("unknown label", ReturnCode::SEMANTIC_ERROR);
    }
}

/******************************************************************************/
class Variable
{
    /* int, bool, string, nil or empty string for yet unknown type */
    private string $type;
    public string $value;

    public function __construct()
    {
        $this->type = "";
        $this->value = "";
    }

    public function set_type(string $type): void
    {
        if ($type !== "int" && $type !== "bool" && $type !== "string" &&
        $type !== "nil")
        {
            throw new InternalErrorException(
                "tried to set invalid type:" . $type
            );
        }
        $this->type = $type;
    }

    public function get_type(): string
    {
        return $this->type;
    }
}

/******************************************************************************/
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
        return trim($this->raw_de->textContent);
    }

    /* returns a lowercase opcode */
    public function get_opcode(): string
    {
        return $this->opcode;
    }

    public function get_first_arg_value(): string
    {
        $element = $this->raw_de->getElementsByTagName("arg1")->item(0);
        if (is_null($element))
        {
            throw new InternalErrorException(
                "getElementsByTagName(\"arg1\")->item(0) is null"
            );
        }
        return trim($element->textContent);
    }
}

/******************************************************************************/
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

    /* throws exception if there is a jump to unknown label, else does
    nothing */
    public function check_jumps(): void
    {
        $labels = $this->get_labels();
        foreach ($this->list as $in)
        {
            $is_jump_instruction = $in->get_opcode() === "jump" ||
            $in->get_opcode() === "jumpifeq" ||
            $in->get_opcode() === "jumpifneq";

            $desired_label = $in->get_first_arg_value();
            $label_exists = in_array($desired_label, $labels);

            if ($is_jump_instruction && !$label_exists)
            {
                dprintstring("unknown label", (string)$in);
                throw new UnknownLabelError;
            }
        }

    }

}

/******************************************************************************/
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

        $ins_list = new InstructionList($dom);
        // $labels = $ins_list->get_labels();
        // dprintinfo("labels", $labels);
        $ins_list->check_jumps();
        return 0;
        // throw new NotImplementedException;
    }
}
