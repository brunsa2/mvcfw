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
        echo '<pre>';
        $this->parseList();
        if(!$this->continueParsing) {
            return;
        }
        $this->scanner->match('End');
        echo '</pre>';
    }
    
    private function parseList() {
        switch($this->scanner->peek()) {
            case 'Text':
            case 'Slash':
            case 'OpeningBrace':
                $this->parseItem();
                if(!$this->continueParsing) {
                    return;
                }
                $this->parseList();
                break;
            case 'End':
                break;
            default:
                $this->error('Unidentified text in routing string');
                break;
        }
    }
    
    private function parseItem() {
        switch($this->scanner->peek()) {
            case 'Text':
                if($this->scanner->match('MatchText') == null) {
                    $this->error('Unexpected character in match text');
                    $this->scanner->advanceOneCharacter();
                    $this->continueAfterError();
                }
                break;
            case 'Slash':
                $this->scanner->match('Slash');
                break;
            case 'OpeningBrace':
                $this->scanner->match('OpeningBrace');
                $this->parsePlaceholder();
                if(!$this->continueParsing) {
                    $this->scanner->advancePastClosingBrace();
                    $this->continueAfterError();
                    return;
                }
                if($this->scanner->match('ClosingBrace') == null) {
                    $this->error('Did not find expected }');
                }
                break;
        }
    }
    
    private function parsePlaceholder() {
        switch($this->scanner->peek()) {
            case 'PlusSign':
                $this->scanner->match('PlusSign');
                if($this->scanner->match('IdentifierText') == null) {
                    $this->error('Did not find expected identifier');
                }
                if(!$this->continueParsing) {
                    return;
                }
                $this->parseRegex();
                break;
            case 'Asterisk':
                $this->scanner->match('Asterisk');
                if($this->scanner->match('IdentifierText') == null) {
                    $this->error('Did not find expected identifier');
                }
                break;
            case 'Text':
                if($this->scanner->match('IdentifierText') == null) {
                    $this->error('Did not find expected identifer');
                }
                if(!$this->continueParsing) {
                    return;
                }
                $this->parseRegex();
                break;
        }
    }
    
    private function parseRegex() {
        switch($this->scanner->peek()) {
            case 'Slash':
                $this->scanner->match('Slash');
                if($this->scanner->match('RegexText') == null) {
                    $this->error('Did not find expected regex');
                }
                if(!$this->continueParsing) {
                    return;
                }
                if($this->scanner->match('Slash') == null) {
                    $this->error('Did not find expected /');
                }
                break;
            case 'ClosingBrace':
                break;
            default:
                $this->error('Did not find expected regex string or }');
                break;
        }
    }
}