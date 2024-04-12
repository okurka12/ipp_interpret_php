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
// MARK:Exceptions
class UnknownLabelError extends IPPException
{
    public function __construct()
    {
        parent::__construct("unknown label", ReturnCode::SEMANTIC_ERROR);
    }
}

class FrameAccessError extends IPPException
{
    public function __construct()
    {
        parent::__construct("trying to acces non-existent frame",
        ReturnCode::FRAME_ACCESS_ERROR);
    }
}

class VariableAccessError extends IPPException
{
    public function __construct()
    {
        parent::__construct("trying to acces non-existent variable",
        ReturnCode::VARIABLE_ACCESS_ERROR);
    }

}

/******************************************************************************/
// MARK:Variable
class Variable
{
    /* int, bool, string, nil or empty string for yet unknown type */
    private string $type;
    // private string $value;
    private string $identifier;

    /* GF LF TF */
    // private string $frame;

    public function __construct(string $identifier)
    {
        $this->type = "";
        // $this->value = "";
        $this->identifier = $identifier;

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

    public function get_iden(): string
    {
        return $this->identifier;
    }
}

/******************************************************************************/
// MARK:Frame
class Frame
{
    /** @var array<Variable> */
    private array $vars;

    public function __construct()
    {
        $this->vars = array();
    }

    /** Inserts `var` into the frame, if the frame contains a variable with
     * the same identifier, the new variable replaces it */
    public function insert_var(Variable $var): void
    {
        /* if the variable is not present, just append to the end */
        if (is_null($this->lookup($var->get_iden())))
        {
            array_push($this->vars, $var);
            return;
        }

        /* else, do a linear search and replace */
        for ($i = 0; $i < count($this->vars); $i++)
        {
            $current_var_iden = $this->vars[$i]->get_iden();
            if ($current_var_iden === $var->get_iden())
            {
                $this->vars[$i] = $var;
                return;
            }
        }
    }

    public function lookup(string $identifier): Variable|null
    {
        foreach ($this->vars as $var) {
            if ($var->get_iden() === $identifier)
            {
                return $var;
            }
        }
        dlog("warning: looked up key not in frame");
        return null;
    }

    public function contains(string $identifier): bool
    {
        foreach ($this->vars as $var) {
            if ($var->get_iden() === $identifier)
            {
                return TRUE;
            }
        }
        return FALSE;

    }
}

/******************************************************************************/
// MARK:FrameStack
class FrameStack
{
    private Frame $gf;

    /** @var array<Frame> */
    private array $lfstack;

    private Frame|null $tf;

    public function __construct()
    {
        $this->gf = new Frame();
        $this->tf = null;
        $this->lfstack = array();
    }

    public function get_gf(): Frame
    {
        return $this->gf;
    }
    public function get_tf(): Frame
    {
        if (is_null($this->tf))
        {
            throw new FrameAccessError;
        }
        return $this->tf;
    }
    public function get_lf(): Frame
    {
        if (count($this->lfstack) === 0)
        {
            throw new FrameAccessError;
        }
        return end($this->lfstack);
    }
    public function create_frame(): void
    {
        $this->tf = new Frame();
    }
    public function push_frame(): void
    {
        array_push($this->lfstack, $this->tf);
    }
    public function pop_frame(): void
    {
        if (count($this->lfstack) === 0)
        {
            throw new FrameAccessError;
        }
        array_pop($this->lfstack);
    }

    /* todo: catch frameaccesserror and throw variableaccesserror instead
    (rc 54 vs 55) */
    public function get_var(string $iden): Variable
    {
        $frame = strtoupper(explode("@", $iden)[0]);
        $identifier = explode("@", $iden)[1];

        $rv = null;
        if ($frame === "GF")
        {
            $rv = $this->gf->lookup($identifier);
        }
        if ($frame === "LF")
        {
            $fr = $this->get_lf();
            $rv = $fr->lookup($identifier);
        }
        if ($frame === "TF")
        {
            $fr = $this->get_tf();
            $rv = $fr->lookup($identifier);
        }
        if (is_null($rv))
        {
            throw new VariableAccessError;
        }
        return $rv;
    }
    public function insert_var(string $iden): void
    {
        $frame = strtoupper(explode("@", $iden)[0]);
        $identifier = explode("@", $iden)[1];
        $var = new Variable($identifier);
        if ($frame === "GF")
        {
            $this->gf->insert_var($var);
        }
        if ($frame === "LF")
        {
            $fr = $this->get_lf();
            $fr->insert_var($var);
        }
        if ($frame === "TF")
        {
            $fr = $this->get_tf();
            $fr->insert_var($var);
        }
    }
}

/******************************************************************************/
// MARK:Instruction
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

    /* compare instructions by order */
    public function __compareTo(Instruction $other): int
    {
        if ($this->order < $other->order)
        {
            return -1;
        }
        if ($this->order > $other->order)
        {
            return 1;
        }
        return 0;
    }

    public function execute(FrameStack &$fs): void
    {
        dprintstring("executing", $this->opcode . " order: " .
        (string)$this->order);
        // todo
    }

    public function get_order(): int
    {
        return $this->order;
    }

}

/******************************************************************************/
// MARK:InstructionList
class InstructionList
{
    /** @var array<Instruction> */
    public array $list;

    public function __construct(DOMDocument $dom)
    {
        $this->list = array();
        foreach($dom->getElementsByTagName("instruction") as $inele) {
            array_push($this->list, new Instruction($inele));
            dprintstring("processed instruction", end($this->list));
        }
        asort($this->list);  // sort by order attribute
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

            if ($is_jump_instruction)
            {
                dlog("checking jump instruction order " .
                (string)$in->get_order());

                /* only get the first argument if its a jump ins */
                $desired_label = $in->get_first_arg_value();
                $label_exists = in_array($desired_label, $labels);

                if (!$label_exists)
                {
                    dprintstring("unknown label", (string)$in);
                    throw new UnknownLabelError;
                }
            }
        }

    }

    public function dprint_instructions(): void
    {
        foreach ($this->list as $ins) {
            dprintstring("instruction", (string)$ins);
        }
    }

}

/******************************************************************************/
// MARK: Interpreter
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
        $ins_list->dprint_instructions();
        // $labels = $ins_list->get_labels();
        // dprintinfo("labels", $labels);
        $ins_list->check_jumps();
        return 0;
        // throw new NotImplementedException;
    }
}
