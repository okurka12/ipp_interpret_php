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
use IPP\Core\Settings;
use Stringable;
use ValueError;

// use IPP\Student\umlT;

/* FUNCTIONS */
// MARK:Functions
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
    dprint_stderr($s . "\n");
}

/**
 * Compare instructions by order
 */
function compare_instructions(Instruction $a, Instruction $b): int
{
    return $a->comp_by_order($b);
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
        parent::__construct("trying to access non-existent variable",
        ReturnCode::VARIABLE_ACCESS_ERROR);
    }
}

class VariableRedefinitionError extends IPPException
{
    public function __construct()
    {
        parent::__construct("trying to redefine variable",
        ReturnCode::SEMANTIC_ERROR);
    }
}
class XMLStructureError extends IPPException
{
    public function __construct()
    {
        parent::__construct("invalid XML structure",
        ReturnCode::INVALID_SOURCE_STRUCTURE);
    }

}
class IPPTypeError extends IPPException
{
    public function __construct(string $when)
    {
        parent::__construct("invalid operand types: " . $when,
        ReturnCode::OPERAND_TYPE_ERROR);
    }
}

// todo: before submission comment out ippcore NotImplementedException
// and uncomment this
// class NotImplementedException extends IPPException
// {
//     public function __construct()
//     {
//         parent::__construct("not implemented", 0);
//     }
// }

/******************************************************************************/
// MARK:Variable
class Variable
{
    /* int, bool, string, nil or empty string for yet unknown type */
    private string $type;
    private string $value;
    private string $identifier;

    /* GF LF TF */
    // private string $frame;

    public function __construct(string $identifier)
    {
        dlog("constructing variable " . $identifier);
        $this->type = "";
        // $this->value = "";
        $this->identifier = $identifier;

    }

    /** set both value and type */
    public function set_value(string $type, string $value): void
    {
        /* check validity of type just to be sure */
        if ($type !== "int" && $type !== "bool" && $type !== "string" &&
        $type !== "nil")
        {
            throw new InternalErrorException(
                "tried to set invalid type: " . $type
            );
        }
        $this->type = $type;
        $this->value = $value;
    }


    public function get_type(): string
    {
        return $this->type;
    }
    public function get_value(): string
    {
        return $this->value;
    }

    public function get_iden(): string
    {
        return $this->identifier;
    }

    /**
     * if this <  other, returns -1
     * if this == other, returns  0
     * if this >  other, returns  1
     */
    public function compare_int(Variable $other): int
    {
        if ($this->get_type() !== "int" || $other->get_type() !== "int")
        {
            throw new IPPTypeError("tried to compare non-integer variables as
            integers");
        }
        $a = (int)$this->get_value();
        $b = (int)$this->get_value();
        if ($a < $b)
        {
            return -1;
        }
        if ($a > $b)
        {
            return 1;
        }
        return 0;

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

    /**
     * This method looks for a variable in the frame stack, but if it doesn't
     * find it, it may throw a VariableAccessError OR a FrameAccessError
     */
    private function lookup_internal(string $iden): Variable
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

    public function lookup(string $identifier): Variable
    {
        /* catch frameaccesserror and throw variableaccesserror
        instead (rc 54 vs 55) */
        try
        {
            $var = $this->lookup_internal($identifier);
        }
        catch (FrameAccessError)
        {
            throw new VariableAccessError();
        }
        return $var;
    }

    /**
     * insert `var_to_insert` whose identifier contains the frame specification
     */
    public function insert_var(Variable $var_to_insert): void
    {
        /** identifier to insert with the frame spec. (eg. LF@a ) */
        $iden_to_insert = $var_to_insert->get_iden();
        $frame = strtoupper(explode("@", $iden_to_insert)[0]);
        $identifier = explode("@", $iden_to_insert)[1];
        $var = new Variable($identifier);
        // $var->set_value(
        //     $var_to_insert->get_type(),
        //     $var_to_insert->get_value()
        // );
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
    /** raw DOM element of this instruction */
    private DOMElement $raw_de;

    /** instruction opcode, always lowercase */
    private string $opcode;

    private int $order;
    public function __construct(DOMElement $de)
    {
        $this->raw_de = $de;
        $this->opcode = strtolower($de->getAttribute("opcode"));
        $this->order = (int)$de->getAttribute("order");
        $this->check_xml();
    }

    /**
     * checks if self's DOMelement doesnt contain weird children, in case of
     * error, throws exception, else does nothing
     */
    private function check_xml(): void
    {

        $err1 = count($this->raw_de->getElementsByTagName("instruction")) > 0;
        $err2 = count($this->raw_de->getElementsByTagName("program")) > 0;

        if ($err1 || $err2)
        {
            throw new XMLStructureError;
        }
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

    /** @return string a lowercase opcode */
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
    public function get_second_arg_value(): string
    {
        $element = $this->raw_de->getElementsByTagName("arg2")->item(0);
        if (is_null($element))
        {
            throw new InternalErrorException(
                "getElementsByTagName(\"arg2\")->item(0) is null"
            );
        }
        return trim($element->textContent);
    }
    public function get_third_arg_value(): string
    {
        $element = $this->raw_de->getElementsByTagName("arg3")->item(0);
        if (is_null($element))
        {
            throw new InternalErrorException(
                "getElementsByTagName(\"arg3\")->item(0) is null"
            );
        }
        return trim($element->textContent);
    }

    /* compare instructions by order */
    public function comp_by_order(Instruction $other): int
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

    /**
     * gets the 'type' attribute of `n`th argument
     */
    public function get_type_attr(int $n): string
    {
        /* get the DOMElement of second argument */
        $tagname = "arg" . (string)$n;
        $arg_de =
        $this->raw_de->getElementsByTagName($tagname)->item(0);
        if ( is_null($arg_de) )
        {
            throw new InternalErrorException("couldn't find " . $tagname);
        }

        /* get the DOMNode of the type attribute */
        $type_attr =
            $arg_de->attributes->getNamedItem("type");
        if ( is_null($type_attr) )
        {
            throw new InternalErrorException("missing type attribute of " .
            $tagname);
        }

        /* get the NodeValue of the type attribute (var int bool string
        nil) */
        $type = $type_attr->nodeValue;
        if ( is_null($type) )
        {
            throw new InternalErrorException("type attribute of arg" .
                "has no value");
        }

        return $type;

    }

    private function unescape_string(string $s): string
    {
        $pattern = '/\\\\([0-9]{1,3})/';
        $output = preg_replace_callback($pattern, function($matches) {
            return chr(intval($matches[1]));
        }, $s);
        if (is_null($output))
        {
            return $s;
        }
        return $output;
    }

    /**
     * gets the contents of the XML argn tag
     * note: if the XML attribute type is 'var', this method returns the
     * identifier of the var
     */
    public function get_nth_arg_value(int $n): string
    {
        if ($n === 1)
        {
            return $this->get_first_arg_value();
        }
        if ($n === 2)
        {
            return $this->get_second_arg_value();
        }
        if ($n === 3)
        {
            return $this->get_third_arg_value();
        }
        throw new InternalErrorException("tried to get " . (string)$n .
        "argument");
    }

    /**
     * interpret `n`'th operand (var/int) as integer
     */
    private function get_int_operand(int $n, FrameStack $fs): int
    {

        $type = $this->get_type_attr($n);
        if ($type !== "var" && $type !== "int")
        {
            throw new IPPTypeError((string)$this);
        }


        /* extract src1 value */
        if ($type === "var")
        {
            $var = $fs->lookup($this->get_nth_arg_value($n));
            if ($var->get_type() !== "int")
            {
                throw new IPPTypeError((string)$this);
            }
            $value = (int)$var->get_value();
        }
        else
        {
            $value = (int)$this->get_nth_arg_value($n);
        }
        return $value;

    }

    /**
     * gets the nth arg value, but unlike `get_nth_arg_value`,
     * if its a var, it looks for the variable in `fs` and gets its value
     * note: if its a string constant, un-escapes it
     */
    private function get_nth_arg_value_resolve(int $n, FrameStack $fs): string
    {
        $type_attr = $this->get_type_attr($n);
        $xml_value = $this->get_nth_arg_value($n);
        if ($type_attr === "var")
        {
            $var = $fs->lookup($xml_value);
            return $var->get_value();
        }
        if ($type_attr === "string")
        {
            return $this->unescape_string($xml_value);
        }
        return $xml_value;
    }
    /**
     * gets the nth arg value, but unlike `get_type_attr`,
     * if its a var, it looks for the variable in `fs` and gets its type
     */
    private function get_nth_arg_type_resolve(int $n, FrameStack $fs): string
    {
        $type_attr = $this->get_type_attr($n);
        $xml_value = $this->get_nth_arg_value($n);
        if ($type_attr === "var")
        {
            $var = $fs->lookup($xml_value);
            return $var->get_type();
        }
        return $type_attr;
    }

    /**
     * executes instruction and returns a label that should be jumped to,
     * if there is no jump, returns empty string
     */
    public function execute(FrameStack $fs, Interpreter $inter): string
    {
        dprintstring("executing", $this->opcode . " order: " .
        (string)$this->order);

        $jump_to_label = "";

        // MARK:_move
        if ($this->get_opcode() === "move")
        {
            $target_iden = $this->get_first_arg_value();

            /* int, bool, string, nil, label, type, var */
            $src_type = $this->get_type_attr(2);

            /* if its a string, unescape it */
            if ($src_type === "string")
            {
                $src_value = $this->unescape_string(
                    $this->get_second_arg_value()
                );
            }

            /* if its not a string, copy the raw value */
            else
            {
                $src_value = $this->get_second_arg_value();
            }

            /* finally, if its a var, obtain both type and value from the
            source variable (and overwrite anythinbg that was set above) */
            if ($src_type === "var") {
                $src_var = $this->get_nth_arg_value(2);
                $src_var = $fs->lookup($src_var);
                $src_type = $src_var->get_type();
                $src_value = $src_var->get_value();

            }

            $target_var = $fs->lookup($target_iden);
            $target_var->set_value($src_type, $src_value);
        }
        // MARK:_defvar
        else if ($this->get_opcode() === "defvar")
        {
            /* identifier containing the frame (like GF@a) */
            $iden = $this->get_first_arg_value();

            try
            {
                $fs->lookup($iden);
                $var_already_present = TRUE;
            }
            catch (FrameAccessError|VariableAccessError)
            {
                $var_already_present = FALSE;
            }
            if ($var_already_present)
            {
                throw new VariableRedefinitionError;
            }
            $var = new Variable($iden);
            $fs->insert_var($var);
        }
        // MARK:_createframe
        else if ($this->get_opcode() === "createframe")
        {
            $fs->create_frame();
        }
        else if ($this->get_opcode() === "pushframe")
        {
            $fs->push_frame();
        }
        else if ($this->get_opcode() === "popframe")
        {
            $fs->pop_frame();
        }
        // MARK:_write
        else if ($this->get_opcode() === "write")
        {
            $type = $this->get_type_attr(1);

            if ($type == "int")
            {
                $inter->print($this->get_first_arg_value());
            }
            else if ($type == "string")
            {
                $text = $this->get_first_arg_value();
                $inter->print($this->unescape_string($text));
            }
            else if ($type == "var")
            {
                $var = $fs->lookup($this->get_first_arg_value());
                $text = $var->get_value();
                $inter->print($text);
            }
            else
            {
                throw new NotImplementedException;
            }
        }
        // MARK:add,mul,idiv
        else if ($this->get_opcode() === "add")
        {
            $target_var = $fs->lookup($this->get_first_arg_value());
            $src1_value = $this->get_int_operand(2, $fs);
            $src2_value = $this->get_int_operand(3, $fs);

            dlog("adding " . (string)$src1_value . " + " .
                (string)$src2_value);
            $result = $src1_value + $src2_value;

            $target_var->set_value("int", (string)$result);

        }
        else if ($this->get_opcode() === "mul")
        {
            $target_var = $fs->lookup($this->get_first_arg_value());
            $src1_value = $this->get_int_operand(2, $fs);
            $src2_value = $this->get_int_operand(3, $fs);

            dlog("multiplying " . (string)$src1_value . " * " .
                (string)$src2_value);
            $result = $src1_value * $src2_value;

            $target_var->set_value("int", (string)$result);

        }
        else if ($this->get_opcode() === "idiv")
        {
            $target_var = $fs->lookup($this->get_first_arg_value());
            $src1_value = $this->get_int_operand(2, $fs);
            $src2_value = $this->get_int_operand(3, $fs);

            dlog("dividing " . (string)$src1_value . " / " .
            (string)$src2_value);
            $result = intdiv($src1_value, $src2_value);

            $target_var->set_value("int", (string)$result);

        }
        // MARK:_jump
        else if ($this->get_opcode() === "jump")
        {
            $jump_to_label = $this->get_nth_arg_value(1);
        }
        else if ($this->get_opcode() === "jumpifeq")
        {
            $first_type = $this->get_nth_arg_type_resolve(2, $fs);
            $second_type = $this->get_nth_arg_type_resolve(3, $fs);
            $first_value = $this->get_nth_arg_value_resolve(2, $fs);
            $second_value = $this->get_nth_arg_value_resolve(3, $fs);

            $one_is_nil = $first_type === "nil" || $second_type === "nil";
            $should_jump = FALSE;

            if (!$one_is_nil && $first_type !== $second_type)
            {
                throw new IPPTypeError((string)$this);
            }
            if (!$one_is_nil)
            {
                if ($first_type === "int")
                {
                    $should_jump = $first_value === $second_value;
                }
            }

            if ($one_is_nil || $should_jump)
            {
                $jump_to_label = $this->get_nth_arg_value(1);
            }
        }
        else if ($this->get_opcode() === "label")
        {
            /* do nothing */
        }

        // MARK:_dprint, _break
        else if ($this->get_opcode() === "dprint")
        {
            dlog("warning: dprint does nothing");
        }
        else if ($this->get_opcode() === "break")
        {
            dlog("warning: break does nothing");
        }
        // MARK:_else
        else
        {
            throw new NotImplementedException;
        }

        return $jump_to_label;
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
        $this->check_xml($dom);
        $this->list = array();
        foreach($dom->getElementsByTagName("instruction") as $inele) {
            array_push($this->list, new Instruction($inele));
            dprintstring("processed instruction", end($this->list));
        }

        /* sort by order attribute */
        usort($this->list, "IPP\Student\compare_instructions");

        return;
    }

    private function check_xml(DOMDocument $dom): void
    {
        $arg1s = $dom->getElementsByTagName("arg1");
        $arg2s = $dom->getElementsByTagName("arg2");
        $arg3s = $dom->getElementsByTagName("arg3");

        $arglists = array($arg1s, $arg2s, $arg3s);

        foreach ($arglists as $arglist)
        {
            foreach ($arglist as $argelement)
            {
                $parent = $argelement->parentNode;
                if (is_null($parent))
                {
                    throw new XMLStructureError;
                }
                if ($parent->nodeName !== "instruction")
                {
                    throw new XMLStructureError;
                }
            }
        }
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

    /**
     * throws exception if there is a jump to unknown label, else does
     * nothing
     */
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
            dprintstring("IL-instruction", (string)$ins);
        }
    }

    /**
     * executes instructions starting from `label` (if empty, starts from
     * the first instruction)
     * if there occurs a jump, returns a label that should be jumped to
     * if it reaches the end, returns empty string
     */
    private function execute_from(Interpreter $inter, FrameStack $fs, string $label): string
    {
        $reached_label = $label === "";

        foreach ($this->list as $ins) {
            if (!$reached_label)
            {
                $reached_label = $ins->get_opcode() === "label" && $ins->get_nth_arg_value(1) === $label;
                continue;
            }
            $jumpto = $ins->execute($fs, $inter);
            if ($jumpto !== "")
            {
                return $jumpto;
            }
        }
        return "";
    }

    /** executes all the instructions in the list */
    public function execute(Interpreter $inter): void
    {
        $fs = new FrameStack;
        $jumpto = "";
        $done = FALSE;
        while (!$done)
        {
            $jumpto = $this->execute_from($inter, $fs, $jumpto);
            $done = $jumpto === "";
        }
    }


}

/******************************************************************************/
// MARK: Interpreter
class Interpreter extends AbstractInterpreter
{
    // use umlT;

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
        $ins_list->execute($this);
        return 0;
        // throw new NotImplementedException;
    }

    public function print(string $s): void
    {
        $this->stdout->writeString($s);
    }
}
