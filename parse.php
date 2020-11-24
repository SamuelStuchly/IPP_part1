<?php
/**
 * Author: Samuel Stuchly
 * Login: xstuch06
 * Date: 3/3/2019
 * Last updated: 3/12/2019
 */

# ======== LIST OF REGULAR EXPRESSIONS =========
$ippcode = "/^\.ippcode19$/i";
$comment = "/#.*/u";
$label =   "/^[\\$-&%*!?_a-zA-Z]{1}[\\$-&%*!?\w]*$/u";
$variable =  "/^(LF|GF|TF){1}@[\\$-&%*!?_a-zA-Z]{1}[\\$-&%*!?\w]*$/";
$constant =  "/(^bool@(true|false)$)|(^nil@nil$)|(^int@[-+]?[0-9]+$)|(^string@((\\\[0-9]{3})|[^\\\])*$)/u";
$type = "/^(bool|int|string)\$/u";
$symbol =  "/(^(LF|GF|TF){1}@[\\$-&%*!?_a-zA-Z]{1}[\\$-&%*!?\w]*$)|((^bool@(true|false)$)|(^nil@nil$)|(^int@[+-]?[0-9]+$)|(^string@((\\\[0-9]{3})|[^\\\])*$))/u";
$spaces = "/[^\S\x0a\x0d]+/u";

# ============= PARAMETERS ==============
$params = array ("--labels","--jumps","--loc","--comments");

# ============= COUNTERS ===============
$intructCounter = 1;
$commentCounter = 0;
$jumpCounter = 0;
$labelCounter = 0;
$labelsArray = array();

# ================ FUNCTIONS ====================

/**
 * Function checks if script is being run with correct arguments and either throws error, executes argument --help or does nothing if everything is correct
 */
function check()
{
    global $params,$argv,$argc;

    if (($argv[1] == "--help") and ($argc == 2))
    {
        print("Script reads source code in IPPcode19 from standard input,checks lexical and syntactic correctness of given code and outputs XML representation of the program to the standard output.\n");
        exit(0);
    }
    elseif ($argc == 1)
    {
        return;
    }
    elseif ($argc > 1)
    {
        $index = 1;
        while($argc != $index)
        {
            if (((in_array($argv[$index],$params)) or (preg_match("/^--stats=/",$argv[$index]))))
            {
               ;
            }
            else {
                fwrite(STDERR," incorrect arguments");
                exit(10);
            }
            $index++;
        }
    }
    else
        {
            fwrite(STDERR," too many arguments");
            exit(10);
        }
}


/**
 * Function gets type of argument of instruction and returns text to be written as type attribute in xml element
 * @param $type //string with type
 * @param $string // string containing the argument of instruction
 * @return string
 */
function getTypeFromSymb($type, $string)
{
    if ($type == "symb")
    {
        $symb = explode("@",$string);

        switch ($symb[0]) {
            case "int":
                return "int ";

            case "bool":
                return "bool";

            case "string":
                return "string";
            case "nil":
                return "nil";

            case "GF":
            case "LF":
            case "TF":
                return "var";
            //if program works correct default case should never be accessed
            default:
                return "im_confused"; //for testing
        }
    }
    else {return $type;}
}

/**
 * Function checks correctness of arguments of instruction
 * @param $first // string of first argument
 * @param $array // array of words on from the line
 * @param $operandType1 // type of operand (argument of instruction)
 */
function oneoperand ($first, $array, $operandType1)
{
    global $xml;
    if ((preg_match($first,$array[1])) and ($array[2] == ""))
    {
        generateInstruct($array);
        generateArgument("arg1",getTypeFromSymb($operandType1,$array[1]),$array[1]);
        $xml->endElement();
    }
    else
    {
        //syntax error
        fwrite(STDERR,"incorrect number or format of operands");
        exit(23);
    }
}


/**
 * Function checks correctness of arguments of instructions with two operands(arguments)
 * @param $first // string of first argument
 * @param $second // string of second argument
 * @param $array // array of words on from the line
 * @param $operandType1 // type of first operand (argument of instruction)
 * @param $operandType2 // type of second operand (argument of instruction)
 */
function twooperands ($first, $second, $array, $operandType1, $operandType2)
{
    global $xml;
    if ((preg_match($first,$array[1])) and (preg_match($second,$array[2])) and ($array[3] == ""))
    {
        generateInstruct($array);
        generateArgument("arg1",getTypeFromSymb($operandType1,$array[1]),$array[1]);
        generateArgument("arg2",getTypeFromSymb($operandType2,$array[2]),$array[2]);
        $xml->endElement();
    }
    else
        {
            fwrite(STDERR,"incorrect number or format of operands"); //syntax error
            exit(23);
        }
}


/**
 * Function checks correctness of arguments of instructions with three operands(arguments)
 * @param $first  // string of first argument
 * @param $second // string of second argument
 * @param $third // string of third argument
 * @param $array // array of words on from the line
 * @param $operandType1 // type of first operand (argument of instruction)
 * @param $operandType2 // type of second operand (argument of instruction)
 * @param $operandType3 // type of third operand (argument of instruction)
 */
function threeoperands ($first, $second, $third, $array, $operandType1, $operandType2, $operandType3)
{
    global $xml;
    if ((preg_match($first,$array[1])) and (preg_match($second,$array[2])) and (preg_match($third,$array[3])) and ($array[4] == ""))
    {
        generateInstruct($array);
        generateArgument("arg1",getTypeFromSymb($operandType1,$array[1]),$array[1]);
        generateArgument("arg2",getTypeFromSymb($operandType2,$array[2]),$array[2]);
        generateArgument("arg3",getTypeFromSymb($operandType3,$array[3]),$array[3]);
        $xml->endElement();
    }
    else
        {
            //syntax error
            fwrite(STDERR,"incorrect number or format of operands");
            exit(23);
        }
}

/**
 * Function parses first line, checks header and generetes it in xml
 * @param $file
 */
function First_line($file)
{
    global $xml,$comment,$ippcode,$spaces,$commentCounter ;

    $firstline = fgets($file);
    $firstline = trim($firstline);
    if (preg_match("/#/",$firstline))
    {
        $commentCounter++;
    }
    $firstline = preg_replace($comment, " ", $firstline);
    $fwords = preg_split($spaces, $firstline);

    if (preg_match($ippcode,$fwords[0]) and ($fwords[1] == ""))
    {
        //moze byt komentar ale nic ine
        $xml ->startDocument('1.0','UTF-8');
        $xml->startElement('program');
        $xml->writeAttribute('language',"IPPcode19");
        return;
    }
    else
        {
            fwrite(STDERR,"incorrector missing header");
            exit(21);
        }
}

/**
 * Function parses the rest of file
 * @param $file
 */
function parseLines ($file)
{
    global $xml,$variable,$label,$symbol,$comment,$type,$spaces,$commentCounter,$labelCounter,$jumpCounter,$labelsArray;

    while (!feof($file))
    {
        $line = fgets($file);
        $line = trim($line);
        if (preg_match("/#/",$line))
        {
            $commentCounter++;
        }
        $line = preg_replace($comment, " ", $line);
        $words = preg_split($spaces, $line);

        if (preg_match("/^CREATEFRAME$|^PUSHFRAME$|^POPFRAME$|^RETURN$|^BREAK$/i", $words[0]))
        {
            //<instuct>
            if (preg_match("/^RETURN$/i",$words[0]))
            {
                $jumpCounter++;
            }
            if ($words[1] == "")
            {
                //is ok
                generateInstruct($words);
                $xml->endElement();
            }
            else {
                fwrite(STDERR,"too many operands");
                exit(23);
            }
        }
        elseif (preg_match("/^CALL$|^LABEL$|^JUMP$/i", $words[0]))
        {
            //<instuct> <label>
            if(preg_match("/^LABEL$/i",$words[0]))
            {
                if (!(in_array($words[1],$labelsArray)))
                {
                    array_push($labelsArray,$words[1]);
                    $labelCounter++;
                }
            }
            else{
                $jumpCounter++;
            }
            oneoperand($label,$words,"label");
        }
        elseif (preg_match("/^JUMPIFEQ$|^JUMPIFNEQ$/i", $words[0]))
        {
        //<instuct> <label> <symb> <symb>
            $jumpCounter++;
        threeoperands($label,$symbol,$symbol,$words,"label","symb",'symb');
        }
        elseif (preg_match("/^PUSHS$|^WRITE$|^EXIT$|^DPRINT$/i", $words[0]))
        {
            //<instuct> <symb>

            oneoperand($symbol,$words,"symb");
        }
        elseif (preg_match("/^DEFVAR$|^POPS$/i", $words[0]))
        {
            // <instuct> <var>
            oneoperand($variable,$words,"var");
        }
        elseif (preg_match("/^ADD$|^SUB$|^MUL$|^IDIV$|^LT$|^GT$|^EQ$|^AND$|^OR$|^NOT$|^STRI2INT$|^CONCAT$|^GETCHAR$|^SETCHAR$/i", $words[0]))
        {
            // <instuct> <var> <symb> <symb>
            threeoperands($variable,$symbol,$symbol,$words,"var","symb","symb");
        }
        elseif (preg_match("/^MOVE$|^INT2CHAR$|^TYPE$|^STRLEN$/i", $words[0]))
        {
            //<instuct> <var> <symb>
            twooperands($variable,$symbol,$words,"var",'symb');
        }
        elseif (preg_match("/^READ$/i", $words[0]))
        {
            // <instuct> <var> <type>
            twooperands($variable,$type,$words,"var","type");
        }//docasne
        elseif ($words[0]=="")
        {
            ; // no instruction on this line
        }
        else
        {
            fwrite(STDERR,"incorrector or unknown opcode ");
            exit(22);
        }
    }
}

/**
 * Function makes text for atributes in xml
 * @param $typOfoperand // type based on which function chooses text options
 * @param $textstring // string with value
 * @return string  //text to be written
 */
function makeText ($typOfoperand, $textstring)
{
    if ($typOfoperand == "var")
    {
        $myArray = explode("@", $textstring);
        strtoupper($myArray[0]);
        $myArray[1] = str_replace("&", "&amp", $myArray[1]);
        return $myArray[0] . "@".$myArray[1];
    }
    elseif ($typOfoperand == "type")
    {
        return $textstring;
    }
    elseif ($typOfoperand == "label")
    {
        $textstring  = str_replace("&", "&amp", $textstring);
        return $textstring;
    }
    else {
        if ($typOfoperand == "string")
        {
            // tu moze byt chyba kedze nerozumiem co znamena ze neprekladajte escape sekvence
            $textstring = replaceXmlEntity($textstring);
        }
        if ($typOfoperand == "bool")
        {
            strtolower($textstring);
        }
        $myArray = explode("@", $textstring);
        return $myArray[1];

    }
}

/**
 * Function replaces characters that could cause trouble in xml format with xml entities
 * @param $textstring  //  string that will be printed into xml
 * @return string //  string with replaced characters
 */
function replaceXmlEntity($textstring)
{
    $textstring  = preg_replace("/\\\(038)|&/", "&amp", $textstring);
    $textstring  = preg_replace( "/\\\(060)|</", "&lt", $textstring);
    $textstring  = preg_replace("/\\\(062)|>/", "&gt", $textstring);
    $textstring  = preg_replace("/\\\(039)|\"/", "&apos", $textstring);
    $textstring  = preg_replace("/\\\(034)|'/", "&quot", $textstring);
    return $textstring;
}

#============ XML FUNCTIONS ====================

/**
 * Function generates XML element for intruction
 * @param $array // array of words on one line
 */
function generateInstruct($array)
{
    global $intructCounter,$xml ;
    $xml->startElement('instruction');
    $xml->writeAttribute('order',$intructCounter++);
    $xml->writeAttribute('opcode',strtoupper($array[0]));
}

/**
 * Function generates XML element for argument
 * @param $argstring  // arg + order of argument of the instruction
 * @param $typeofoperand // string describing type of argument
 * @param $textstring  // string with value of argument
 */
function generateArgument($argstring, $typeofoperand, $textstring)
{
    global $xml;
    $xml->startElement($argstring);
    $xml->writeAttribute('type',$typeofoperand);
    $xml->text(makeText($typeofoperand, $textstring));
    $xml->endElement();
}

# ============= STATP =========================

/**
 *Function checks arguments for statistics file
 */
function StatP()
{
    global $argc,$argv;

    $parameter = array("stats:");
    $option = getopt("", $parameter);

    if ($option["stats"]) {

        makeStatP($option);
    }
    elseif ($argc == 2)
    {
        //this option might never be accessed
        if ($argv[1]!= "--help")
        {
            fwrite(STDERR,"missing argument 'stats' !");
            exit(10);
        }
    }
    elseif ($argc == 1){return;}
    else {
        fwrite(STDERR,"missing argument 'stats'  !");
        exit(10);
    }
}

/**
 * Function writes statistics to the stats file based on the order of arguments provided
 * @param $option  //associative array that contains file for statistics
 */
function makeStatP ($option)
{
    global $labelCounter,$jumpCounter,$intructCounter,$commentCounter,$params,$argc,$argv;

    $statFile = $option["stats"] or exit(10);
    $statp = fopen($statFile, "w+") or exit(12);

    $i = 0;
    while ($argc != $i)
    {
        foreach ($params as $param) {
            if ($argv[$i] == $param) {
                switch ($param) {
                    case "--labels":
                        fwrite($statp, $labelCounter."\n");
                        break;
                    case "--jumps":
                        fwrite($statp, $jumpCounter."\n");
                        break;
                    case "--loc":
                        fwrite($statp, ($intructCounter - 1)."\n");
                        break;
                    case "--comments":
                        fwrite($statp, $commentCounter."\n");
                        break;
                    default:
                        //just do noting i guess
                        fwrite(STDERR," incorrect arguments");
                        exit(10);
                }
            }
        }
        $i++;
    }
    fclose($statp);
}

# ======================  MAIN  ==================================

check();
$xml = new XMLWriter();
$xml ->openMemory();
$file = STDIN;
First_line($file);
parseLines($file);
$xml->endElement();
fclose(STDIN);
$xml->endDocument();
fwrite(STDOUT,$xml->outputMemory());
StatP();

# ================================================================
















