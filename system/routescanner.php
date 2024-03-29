<?php

class RouteScanner {
    private $text, $position;
    
    public function __construct($text) {
        $this->text = $text;
        $this->position = 0;
    }
    
    public function getText() {
        return $this->text;
    }
    
    public function getCaratLine($position) {
        $spaces = '';
        for($spaceIndex = 0; $spaceIndex < $position - 1; $spaceIndex++) {
            $spaces .= ' ';
        }
        return $spaces . '^';
    }
    
    public function peek() {
        if(!$this->hasCharacter()) {
            return EndToken::getTypeName();
        }
        $character = $this->nextCharacter();
        if(self::isOpeningBrace($character)) {
            return OpeningBraceToken::getTypeName();
        } else if(self::isClosingBrace($character)) {
            return ClosingBraceToken::getTypeName();
        } else if(self::isPlusSign($character)) {
            return PlusSignToken::getTypeName();
        } else if(self::isAsterisk($character)) {
            return AsteriskToken::getTypeName();
        } else if(self::isSlash($character)) {
            return SlashToken::getTypeName();
        } else {
            return TextToken::getTypeName();
        }
    }
    
    public function nextCharacter() {
        return substr($this->text, $this->position, 1);
    }
    
    public function advanceOneCharacter() {
        $this->position++;
    }
    
    public function advancePastClosingBrace() {
        while($this->hasCharacter()) {
            $character = $this->nextCharacter();
            if(!$this->isClosingBrace($character)) {
                $this->position++;
            } else {
                $this->position++;
                return;
            }
        }
    }
    
    public function getPosition() {
        return $this->position + 1;
    }
    
    public function match($tokenType) {
        switch($tokenType) {
            case 'End':
                if(!$this->hasCharacter()) {
                    return new EndToken();
                } else {
                    return null;
                }
            case 'OpeningBrace':
                $character = $this->nextCharacter();
                if(self::isOpeningBrace($character)) {
                    $token = new OpeningBraceToken();
                    $this->position += $token->getLength();
                    return $token;
                } else {
                    return null;
                }
            case 'ClosingBrace':
                $character = $this->nextCharacter();
                if(self::isClosingBrace($character)) {
                    $token = new ClosingBraceToken();
                    $this->position += $token->getLength();
                    return $token;
                } else {
                    return null;
                }
            case 'PlusSign':
                $character = $this->nextCharacter();
                if(self::isPlusSign($character)) {
                    $token = new PlusSignToken();
                    $this->position += $token->getLength();
                    return $token;
                } else {
                    return null;
                }
            case 'Asterisk':
                $character = $this->nextCharacter();
                if(self::isAsterisk($character)) {
                    $token = new AsteriskToken();
                    $this->position += $token->getLength();
                    return $token;
                } else {
                    return null;
                }
            case 'Slash':
                $character = $this->nextCharacter();
                if(self::isSlash($character)) {
                    $token = new SlashToken();
                    $this->position += $token->getLength();
                    return $token;
                } else {
                    return null;
                }
            case 'MatchText':
                $text = '';
                if(!$this->hasCharacter()) {
                    return null;
                }
                $character = $this->nextCharacter();
                if(!$this->isMatchTextCharacter($character)) {
                    return null;
                }
                while($this->hasCharacter()) {
                    $character = $this->nextCharacter();
                    if($this->isMatchTextCharacter($character)) {
                        $text .= $character;
                        $this->position++;
                    } else {
                        $token = new MatchTextToken($text);
                        return $token;
                    }
                }
                $token = new MatchTextToken($text);
                return $token;
            case 'IdentifierText':
                $text = '';
                if(!$this->hasCharacter()) {
                    return null;
                }
                $character = $this->nextCharacter();
                if($this->isLetter($character) || $this->isUnderscore($character)) {
                    $text = $character;
                    $this->position++;
                } else {
                    return null;
                }
                $character = $this->nextCharacter();
                if($this->isNotIdentifierCharacter($character)) {
                    $token = new IdentifierToken($text);
                    return $token;
                }
                while($this->hasCharacter()) {
                    $character = $this->nextCharacter();
                    if($this->isAlphanumeric($character) || $this->isUnderscore($character)) {
                        $text .= $character;
                        $this->position++;
                    } else {
                        $token = new IdentifierToken($text);
                        return $token;
                    }
                }
                $token = new IdentifierToken($text);
                return $token;
            case 'RegexText':
                $text = '';
                $numberOfSlashes = 0;
                if(!$this->hasCharacter()) {
                    return null;
                }
                $character = $this->nextCharacter();
                if(!($this->isRegexCharacter($character) || $this->isBackslash($character))) {
                    return null;
                }
                while($this->hasCharacter()) {
                    $character = $this->nextCharacter();
                    if($this->isRegexCharacter($character)) {
                        $text .= $character;
                        $this->position++;
                    } else if($this->isBackslash($character)) {
                        $this->position++;
                        $numberOfSlashes++;
                        $character = $this->nextCharacter();
                        if($this->isBackslash($character) || $this->isSlash($character)) {
                            $text .= $character;
                            $this->position++;
                        } else {
                            return null;
                        }
                    } else {
                        $token = new RegexToken($text, $numberOfSlashes);
                        return $token;
                    }
                }
                $token = new RegexToken($text, $numberOfSlashes);
                return $token;
            default:
                return null;
        }
    }
    
    private function hasCharacter() {
        return $this->position < strlen($this->text);
    }
    
    private static function isMatchTextCharacter($character) {
        return preg_match('/[A-Za-z0-9-._~!$&\'()*+,;=:@]/', $character) > 0;
    }
    
    private static function isNotMatchTextCharacter($character) {
        return preg_match('/[^A-Za-z0-9-._~!$&\'()*+,;=:@]/', $character) > 0;
    }
    
    private static function isRegexCharacter($character) {
        return preg_match('/[^\/\\\\]/', $character) > 0;
    }
    
    private static function isNotRegexCharacter($character) {
        return preg_match('/[\/\\\\]/', $character) > 0;
    }
    
    private static function isAlphanumeric($character) {
        return preg_match('/[A-Za-z0-9]/', $character) > 0;
    }
    
    private static function isLetter($character) {
        return preg_match('/[A-Za-z]/', $character) > 0;
    }
    
    private static function isUnderscore($character) {
        return preg_match('/_/', $character) > 0;
    }
    
    private static function isNotIdentifierCharacter($character) {
        return preg_match('/[^A-Za-z0-9_]/', $character) > 0;
    }
    
    private static function isOpeningBrace($character) {
        return preg_match('/{/', $character) > 0;
    }
    
    private static function isClosingBrace($character) {
        return preg_match('/}/', $character) > 0;
    }
    
    private static function isSlash($character) {
        return preg_match('/\//', $character) > 0;
    }
    
    private static function isBackslash($character) {
        return preg_match('/\\\\/', $character) > 0;
    }
    
    private static function isPlusSign($character) {
        return preg_match('/\+/', $character) > 0;
    }
    
    private static function isAsterisk($character) {
        return preg_match('/\*/', $character) > 0;
    }
}

abstract class Token {
    public function is($tokenClass) {
        return $this instanceof $tokenClass;
    }
    
    public function isNot($tokenClass) {
        return !($this instanceof $tokenClass);
    }
}

class TextToken extends Token {
    protected $text;
    
    public function __construct($text) {
        $this->text = $text;
    }
    
    public function getValue() {
        return $this->text;
    }
    
    public static function getTypeName() {
        return 'Text';
    }
    
    public function getLength() {
        return strlen($this->text);
    }
}

class MatchTextToken extends TextToken {
    public function __toString() {
        return 'T_MATCH_TEXT (' . $this->text . ')';
    }
}

class IdentifierToken extends TextToken {
    public function __toString() {
        return 'T_IDENTIFIER_TEXT (' . $this->text . ')';
    }
}

class RegexToken extends TextToken {
    private $numberOfSlashes;
    
    public function __construct($text, $numberOfSlashes) {
        parent::__construct($text);
        $this->numberOfSlashes = $numberOfSlashes;
    }
    
    public function __toString() {
        return 'T_REGEX_TEXT (' . $this->text . ')';
    }
    
    public function getLength() {
        return parent::getLength() + $this->numberOfSlashes;
    }
}

class OpeningBraceToken extends Token {
    public function __toString() {
        return 'T_OPENING_BRACE';
    }
    
    public static function getTypeName() {
        return 'OpeningBrace';
    }
    
    public function getLength() {
        return 1;
    }
}

class ClosingBraceToken extends Token {
    public function __toString() {
        return 'T_CLOSING_BRACE';
    }
    
    public static function getTypeName() {
        return 'ClosingBrace';
    }
    
    public function getLength() {
        return 1;
    }
}

class PlusSignToken extends Token {
    public function __toString() {
        return 'T_PLUS_SIGN';
    }
    
    public static function getTypeName() {
        return 'PlusSign';
    }
    
    public function getLength() {
        return 1;
    }
}

class AsteriskToken extends Token {
    public function __toString() {
        return 'T_ASTERISK';
    }
    
    public static function getTypeName() {
        return 'Asterisk';
    }
    
    public function getLength() {
        return 1;
    }
}

class SlashToken extends Token {
    public function __toString() {
        return 'T_SLASH';
    }
    
    public static function getTypeName() {
        return 'Slash';
    }
    
    public function getLength() {
        return 1;
    }
}

class EndToken extends Token {
    public function __toString() {
        return 'T_END';
    }
    
    public static function getTypeName() {
        return 'End';
    }
}

?>