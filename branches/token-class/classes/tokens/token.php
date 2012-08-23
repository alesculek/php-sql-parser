<?php
class Token implements Serializable {

    protected $token;

    public function __construct($string) {
        $this->token = $string;
    }

    public function serialize() {
        return serialize($this->token);
    }

    public function unserialize($data) {
        $this->token = unserialize($data);
    }

    public function get() {
        return $this->token;
    }

    /**
     * Ends the given string $haystack with the string $needle?
     * @param string $haystack
     * @param string $needle
     */
    public function endsWith($needle) {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        $start = $length * -1;
        return (substr($this->token, $start) === $needle);
    }

    public function startsWith($needle) {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }
        return (substr($this->token, 0, $length) === $needle);
    }

    public function add($token) {
        $this->token .= $token->get();
    }

    public function isEOL() {
        return ($this->token === "\n" || $this->token === "\r\n");
    }

    public function isBacktick() {
        return ($this->token === "'" || $this->token === "\"" || $this->token === "`");
    }

}
