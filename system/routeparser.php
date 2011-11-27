<?php

class RouteParser {
    private $scanner, $continueParsing = true;
    private $errors = array();
    
    public function __construct($scanner) {
        $this->scanner = $scanner;
    }
    
    private function error($message) {
        $this->continueParsing = false;
        $error = array();
        $error['column'] = $this->scanner->getPosition();
        $error['message'] = $message;
        array_push($this->errors, $error);
    }
    
    public function displayErrors() {
        foreach($this->errors as $error) {
            echo 'Column ', $error['column'], ': ', $error['message'], '<br />';
            echo $this->scanner->getText(), '<br />';
            echo $this->scanner->getCaratLine($error['column']), '<br />';
        }
    }
    
    private function continueAfterError() {
        $this->continueParsing = true;
    }
    
    public function parse() {
        $list = new ListNode();
        $this->parseList($list);
        if(!$this->continueParsing) {
            return null;
        }
        $this->scanner->match('End');
        return $list;
    }
    
    private function parseList($list) {
        switch($this->scanner->peek()) {
            case 'Text':
            case 'Slash':
            case 'OpeningBrace':
                $item = $this->parseItem();
                if(!$this->continueParsing) {
                    return null;
                }
                $list->push($item);
                $this->parseList($list);
                break;
            case 'End':
                return null;
                break;
            default:
                $this->error('Unidentified text in routing string');
                return null;
                break;
        }
    }
    
    private function parseItem() {
        switch($this->scanner->peek()) {
            case 'Text':
                $text = $this->scanner->match('MatchText');
                if($text == null) {
                    $this->error('Unexpected character in match text');
                    $this->scanner->advanceOneCharacter();
                    $this->continueAfterError();
                }
                return new TextNode($text->getValue());
            case 'Slash':
                $this->scanner->match('Slash');
                return new DirectorySeparatorNode();
            case 'OpeningBrace':
                $this->scanner->match('OpeningBrace');
                $placeholder = $this->parsePlaceholder();
                if(!$this->continueParsing) {
                    $this->scanner->advancePastClosingBrace();
                    $this->continueAfterError();
                    return null;
                }
                if($this->scanner->match('ClosingBrace') == null) {
                    $this->error('Did not find expected }');
                }
                return $placeholder;
        }
        return 'Item';
    }
    
    private function parsePlaceholder() {
        switch($this->scanner->peek()) {
            case 'PlusSign':
                $placeholder = new OptionalPlaceholderNode();
                $this->scanner->match('PlusSign');
                $identifier = $this->scanner->match('IdentifierText');
                if($identifier == null) {
                    $this->error('Did not find expected identifier');
                }
                if(!$this->continueParsing) {
                    return null;
                }
                $regex = $this->parseRegex();
                $placeholder->setIdentifier($identifier->getValue());
                $placeholder->setRegex($regex);
                return $placeholder;
            case 'Asterisk':
                $placeholder = new AbsorbingPlaceholderNode();
                $this->scanner->match('Asterisk');
                $identifier = $this->scanner->match('IdentifierText');
                if($identifier == null) {
                    $this->error('Did not find expected identifier');
                }
                $placeholder->setIdentifier($identifier->getValue());
                return $placeholder;
            case 'Text':
                $placeholder = new RequiredPlaceholderNode();
                $identifier = $this->scanner->match('IdentifierText');
                if($identifier == null) {
                    $this->error('Did not find expected identifer');
                }
                if(!$this->continueParsing) {
                    return null;
                }
                $regex = $this->parseRegex();
                $placeholder->setIdentifier($identifier->getValue());
                $placeholder->setRegex($regex);
                return $placeholder;
        }
    }
    
    private function parseRegex() {
        switch($this->scanner->peek()) {
            case 'Slash':
                $this->scanner->match('Slash');
                $regex = $this->scanner->match('RegexText');
                if($regex == null) {
                    $this->error('Did not find expected regex');
                }
                if(!$this->continueParsing) {
                    return;
                }
                if($this->scanner->match('Slash') == null) {
                    $this->error('Did not find expected /');
                }
                return $regex->getValue();
            case 'ClosingBrace':
                return '';
            default:
                $this->error('Did not find expected regex string or }');
                return null;
        }
    }
}

class Node {
    
}

class ListNode extends Node {
    private $items = array();
    
    public function push($item) {
        array_push($this->items, $item);
    }
    
    public function compile() {
        echo '<pre>', print_r($this, true), '</pre><br />';
    }
}

class TextNode extends Node {
    private $text;
    
    public function __construct($text) {
        $this->text = $text;
    }
}

class DirectorySeparatorNode extends Node {
    
}

class PlaceholderNode extends Node {
    protected $identifier;
    
    public function setIdentifier($identifier) {
        $this->identifier = $identifier;
    }
}

class RequiredPlaceholderNode extends PlaceholderNode {
    private $regex = '';
    
    public function setRegex($regex) {
        $this->regex = $regex;
    }
}

class OptionalPlaceholderNode extends PlaceholderNode {
    private $regex = '';
    
    public function setRegex($regex) {
        $this->regex = $regex;
    }
}

class AbsorbingPlaceholderNode extends PlaceholderNode {
    
}