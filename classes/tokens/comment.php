<?php
class Comment extends Token implements Serializable {

    private $inline;

    public function __construct($string) {
        parent::__construct($string);
        $this->inline = isset($string[0]) && isset($string[1]) && $string[0] === '-' && $string[1] === '-';
    }

    public function serialize() {
        return serialize(array('inline' => $this->inline, 'parentData' => parent::serialize()));
    }

    public function unserialize($data) {
        $data = unserialize($data);
        $this->inline = $data['inline'];
        parent::unserialize($data['parentData']);
    }

    public function isInline() {
        return $this->inline;
    }
}
