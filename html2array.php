<?php

/**
 * todo
 * 1. script link 等标签
 * 2. 注释
 * 3. bug
 */

class Html2Array {
    // < 后面可以跟这些字符代表为标签
    public static $kw = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ/";
    // 非闭合标签
    public static $sTag = array(
        'area', 'base', 'br', 'col',
        'command', 'embed', 'hr', 'img',
        'input', 'keygen', 'link', 'meta',
        'param', 'source', 'track', 'wbr',
    );

    private $html = '';
    private $len = 0;
    private $stack = array();
    private $stackCnt = 0;
    public $data = array();

    public function __construct($html)
    {
        $this->html = str_replace("\r\n", "", $html);
        $this->len = strlen($this->html);
    }

    public function parse() {
        $first = $this->len;
        $last = 0;
        $index = 0;
        while (true) {
            $iL = $this->findL($this->html, $this->len, $index);
            if($first>$iL and $iL!=-1) $first = $iL;
            if($iL == -1) {
                $last = $index;
                break;
            }
            $iR = $this->findR($this->html, $this->len, $iL);
            $div = substr($this->html, $iL, $iR-$iL+1);
            $iBlank =strpos($div, ' ') ;
            // 是否有空格 有的话就代表有属性
            if($iBlank === false) {
                $name = substr($div, 1, strlen($div)-2);
                if($name[0]!='/') {
                    if($this->stackCnt > 0
                        and in_array($this->stack[$this->stackCnt-1]['name'], self::$sTag)) {
                        $this->oStack();
                    }
                    $this->iStack(array(
                        'name' => $name,
                        'child' => array(),
                        'l' => $iR,
                    ));
                }
                else {
                    if($this->stackCnt > 0
                        and substr($name, 1) == $this->stack[$this->stackCnt-1]['name']){
                        $start = $this->stack[$this->stackCnt-1]['l']+1;
                        $this->stack[$this->stackCnt-1]['text'] = substr($this->html, $start, $iL-$start);
                        $this->oStack();
                    }
                }
            }
            else {
                $name = substr($div, 1, $iBlank-1);
                $attr = substr($div, $iBlank, strlen($div)-$iBlank-1);
                $attr = $this->mkAttr($attr);
                $this->iStack(array(
                    'name' => $name,
                    'child' => array(),
                    'attr' => $attr,
                    'l' => $iR,
                ));
            }
            $index = $iR+1;
            $this->stack = array_values($this->stack);
        }

        if($first != 0) array_unshift($this->data, substr($this->html, 0, $first));
        if($last < $this->len) $this->data[] = substr($this->html, $last, $this->len-$last);

        return $this->data;
    }


    private function mkAttr($str) {
        $attr = array();
        foreach(explode(' ', $str) as $v) {
            if(empty($v)) continue;
            $index = strpos($v, '=');
            $kk = substr($v, 0, strpos($v, '='));
            $vv = substr($v, $index+2, strlen($v)-strlen($kk)-3);
            $attr[$kk] = $vv;
        }
        return $attr;
    }
    private function findL($str, $len, $index) {
        while($index<$len) {
            $ch = $str[$index];
            if($ch == '<' and strpos(self::$kw, $str[$index+1]) !== false) return $index;
            $index ++;
        }
        return -1;
    }
    private function findR($str, $len, $index) {
        $quote = array('"', "'");
        $curQuote = '';
        while ($index<$len) {
            $ch = $str[$index];
            if(in_array($ch, $quote)) {
                if($curQuote == '') {
                    $curQuote = $ch;
                }elseif($curQuote == $ch) {
                    $curQuote = '';
                }
            }
            if($ch == '>' and $curQuote ==  '') return $index;
            $index ++;
        }
        return -1;
    }
    private function oStack() {
        if($this->stackCnt < 1) return false;
        $item = &$this->stack[$this->stackCnt-1];
        if(isset($item['l'])) unset($item['l']);
        if(empty($item['child'])) unset($item['child']);

        unset($this->stack[$this->stackCnt-1]);
        $this->stackCnt -= 1;

        if(empty($this->stack)) {
            $this->data[] = $item;
            return $item;
        }

        $this->stack[$this->stackCnt-1]['child'][] = $item;
        return $item;
    }
    private function iStack($item) {
        $this->stack[] = &$item;
        $this->stackCnt += 1;
    }
}



$html = <<<HTML
12345
<p title="1<p'>2" id='1<3>"222'>lmx</p>
<img src="src" alt="alt">
<div><span class="lmx">j"<.<span>smx</span>mx</span>qm>x<em><em><span class="em"></span>.</em>bmx</em></div>12345
HTML;

$data = (new Html2Array($html))->parse();

echo json_encode($data), PHP_EOL;

