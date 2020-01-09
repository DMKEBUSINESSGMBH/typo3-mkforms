<?php

//+ Jonas Raoni Soares Silva
//@ http://jsfromhell.com
// taken from http://snippets.dzone.com/posts/show/3128

class CSV
{
    public $cellDelimiter;
    public $valueEnclosure;
    public $rowDelimiter;

    public function __construct($cellDelimiter, $rowDelimiter, $valueEnclosure)
    {
        $this->cellDelimiter = $cellDelimiter;
        $this->valueEnclosure = $valueEnclosure;
        $this->rowDelimiter = $rowDelimiter;
        $this->o = [];
    }

    public function getArray()
    {
        return $this->o;
    }

    public function setArray($o)
    {
        $this->o = $o;
    }

    public function getContent()
    {
        if (!(($bl = strlen($b = $this->rowDelimiter)) && ($dl = strlen($d = $this->cellDelimiter)) && ($ql = strlen($q = $this->valueEnclosure)))) {
            return '';
        }
        for ($o = $this->o, $i = -1; ++$i < count($o);) {
            for ($j = -1; ++$j < count($o[$i]);) {
                (($e = false !== strpos($o[$i][$j], $q)) || false !== strpos($o[$i][$j], $b) || false !== strpos($o[$i][$j], $d))
                && $o[$i][$j] = $q.($e ? str_replace($q, $q.$q, $o[$i][$j]) : $o[$i][$j]).$q;
            }
            $o[$i] = implode($d, $o[$i]);
        }

        return implode($b, $o);
    }

    public function setContent($s)
    {
        $this->o = [];
        if (!strlen($s)) {
            return true;
        }
        if (!(($bl = strlen($b = $this->rowDelimiter)) && ($dl = strlen($d = $this->cellDelimiter)) && ($ql = strlen($q = $this->valueEnclosure)))) {
            return false;
        }
        for ($o = [['']], $this->o = &$o, $e = $r = $c = 0, $i = -1, $l = strlen($s); ++$i < $l;) {
            if (!$e && substr($s, $i, $bl) == $b) {
                $o[++$r][$c = 0] = '';
                $i += $bl - 1;
            } elseif (substr($s, $i, $ql) == $q) {
                $e ? (substr($s, $i + $ql, $ql) == $q ?
                $o[$r][$c] .= substr($s, $i += $ql, $ql) : $e = 0) : (0 == strlen($o[$r][$c]) ? $e = 1 : $o[$r][$c] .= substr($s, $i, $ql));
                $i += $ql - 1;
            } elseif (!$e && substr($s, $i, $dl) == $d) {
                $o[$r][++$c] = '';
                $i += $dl - 1;
            } else {
                $o[$r][$c] .= $s[$i];
            }
        }

        return true;
    }
}
