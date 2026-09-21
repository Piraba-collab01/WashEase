<?php
/*******************************************************************************
* FPDF                                                                         *
* Version: 1.86 - Pure PHP PDF Engine                                          *
* License: Permissive                                                          *
*******************************************************************************/

define('FPDF_VERSION', '1.86');

class FPDF {
    protected $page;               // current page number
    protected $n;                  // current object number
    protected $offsets;            // array of object offsets
    protected $buffer;             // buffer holding in-memory PDF
    protected $pages;              // array containing pages
    protected $state;              // current document state
    protected $isFinished;         // flag for state
    protected $DefOrientation;     // default orientation
    protected $CurOrientation;     // current orientation
    protected $OrientationChanges; // array indicating orientation changes
    protected $k;                  // scale factor (number of points in user unit)
    protected $wPt, $hPt;          // dimensions of page in points
    protected $w, $h;              // dimensions of page in user unit
    protected $lMargin;            // left margin
    protected $tMargin;            // top margin
    protected $rMargin;            // right margin
    protected $bMargin;            // page break margin
    protected $cMargin;            // cell margin
    protected $x, $y;              // current position in user unit
    protected $lasth;              // height of last printed cell
    protected $LineWidth;          // line width in user unit
    protected $CoreFonts;          // array of core font names
    protected $fonts;              // array of used fonts
    protected $FontFamily;         // current font family
    protected $FontStyle;          // current font style
    protected $underline;          // underlining flag
    protected $CurrentFont;        // current font info
    protected $FontSizePt;         // current font size in points
    protected $FontSize;           // current font size in user unit
    protected $DrawColor;          // commands for drawing color
    protected $FillColor;          // commands for filling color
    protected $TextColor;          // commands for text color
    protected $ColorFlag;          // indicates whether fill and text colors are different
    protected $AutoPageBreak;      // automatic page breaking
    protected $PageBreakTrigger;   // threshold to trigger page break
    protected $InHeader;           // flag set when processing header
    protected $InFooter;           // flag set when processing footer
    protected $DefPageSize;
    protected $CurPageSize;
    protected $ws;

    public function __construct($orientation='P', $unit='mm', $size='A4') {
        $this->state = 0;
        $this->page = 0;
        $this->n = 2;
        $this->buffer = '';
        $this->pages = array();
        $this->offsets = array();
        $this->OrientationChanges = array();
        $this->fonts = array();
        $this->CoreFonts = array('courier', 'helvetica', 'times', 'symbol', 'zapfdingbats');
        $this->ws = 0;

        // Scale factor
        if ($unit == 'pt') $this->k = 1;
        elseif ($unit == 'mm') $this->k = 72/25.4;
        elseif ($unit == 'cm') $this->k = 72/2.54;
        elseif ($unit == 'in') $this->k = 72;
        else $this->Error('Incorrect unit: '.$unit);

        // Page sizes
        $size = $this->_getpagesize($size);
        $this->DefPageSize = $size;
        $this->CurPageSize = $size;
        
        // Page orientation
        $orientation = strtolower($orientation);
        if ($orientation == 'p' || $orientation == 'portrait') {
            $this->DefOrientation = 'P';
            $this->w = $size[0];
            $this->h = $size[1];
        } elseif ($orientation == 'l' || $orientation == 'landscape') {
            $this->DefOrientation = 'L';
            $this->w = $size[1];
            $this->h = $size[0];
        } else {
            $this->Error('Incorrect orientation: '.$orientation);
        }
        $this->CurOrientation = $this->DefOrientation;
        $this->wPt = $this->w * $this->k;
        $this->hPt = $this->h * $this->k;

        // Page margins (10 mm)
        $margin = 10;
        $this->SetMargins($margin, $margin);
        // Interior cell margin (1 mm)
        $this->cMargin = 1;
        // Line width (0.2 mm)
        $this->LineWidth = 0.2;
        // Automatic page break
        $this->SetAutoPageBreak(true, 2 * $margin);
        // Set default font
        $this->SetFont('Helvetica', '', 12);
        $this->DrawColor = '0 G';
        $this->FillColor = '0 g';
        $this->TextColor = '0 g';
        $this->ColorFlag = false;
        $this->InHeader = false;
        $this->InFooter = false;
    }

    public function GetX() { return $this->x; }
    public function GetY() { return $this->y; }

    public function SetX($x) {
        if ($x >= 0) $this->x = $x;
        else $this->x = $this->w + $x;
    }

    public function SetY($y) {
        if ($y >= 0) $this->y = $y;
        else $this->y = $this->h + $y;
    }

    public function SetXY($x, $y) {
        $this->SetX($x);
        $this->SetY($y);
    }

    public function Ln($h=null) {
        $this->x = $this->lMargin;
        if ($h === null) $this->y += $this->lasth;
        else $this->y += $h;
    }

    public function SetMargins($left, $top, $right=null) {
        $this->lMargin = $left;
        $this->tMargin = $top;
        $this->rMargin = ($right === null) ? $left : $right;
    }

    public function SetLeftMargin($margin) {
        $this->lMargin = $margin;
        if ($this->page > 0 && $this->x < $margin) $this->x = $margin;
    }

    public function SetTopMargin($margin) {
        $this->tMargin = $margin;
    }

    public function SetRightMargin($margin) {
        $this->rMargin = $margin;
    }

    public function SetAutoPageBreak($auto, $margin=0) {
        $this->AutoPageBreak = $auto;
        $this->bMargin = $margin;
        $this->PageBreakTrigger = $this->h - $margin;
    }

    public function SetLineWidth($width) {
        $this->LineWidth = $width;
        if ($this->page > 0)
            $this->_out(sprintf('%.2F w', $width * $this->k));
    }

    public function Line($x1, $y1, $x2, $y2) {
        $this->_out(sprintf('%.2F %.2F m %.2F %.2F l S', $x1*$this->k, ($this->h-$y1)*$this->k, $x2*$this->k, ($this->h-$y2)*$this->k));
    }

    public function Rect($x, $y, $w, $h, $style='') {
        $op = 'S';
        if ($style=='F') $op = 'f';
        elseif ($style=='FD' || $style=='DF') $op = 'B';
        $this->_out(sprintf('%.2F %.2F %.2F %.2F re %s', $x*$this->k, ($this->h-$y)*$this->k, $w*$this->k, -$h*$this->k, $op));
    }

    public function SetDrawColor($r, $g=null, $b=null) {
        if (($r==0 && $g==0 && $b==0) || $g===null)
            $this->DrawColor = sprintf('%.3F G', $r/255);
        else
            $this->DrawColor = sprintf('%.3F %.3F %.3F RG', $r/255, $g/255, $b/255);
        if ($this->page > 0)
            $this->_out($this->DrawColor);
    }

    public function SetFillColor($r, $g=null, $b=null) {
        if (($r==0 && $g==0 && $b==0) || $g===null)
            $this->FillColor = sprintf('%.3F g', $r/255);
        else
            $this->FillColor = sprintf('%.3F %.3F %.3F rg', $r/255, $g/255, $b/255);
        $this->ColorFlag = ($this->FillColor != $this->TextColor);
        if ($this->page > 0)
            $this->_out($this->FillColor);
    }

    public function SetTextColor($r, $g=null, $b=null) {
        if (($r==0 && $g==0 && $b==0) || $g===null)
            $this->TextColor = sprintf('%.3F g', $r/255);
        else
            $this->TextColor = sprintf('%.3F %.3F %.3F rg', $r/255, $g/255, $b/255);
        $this->ColorFlag = ($this->FillColor != $this->TextColor);
    }

    public function GetStringWidth($s) {
        $s = (string)$s;
        $cw = &$this->CurrentFont['cw'];
        $w = 0;
        $l = strlen($s);
        for ($i=0; $i<$l; $i++)
            $w += isset($cw[$s[$i]]) ? ord($cw[$s[$i]]) : 500;
        return $w * $this->FontSize / 1000;
    }

    public function SetFont($family, $style='', $size=0) {
        if ($family=='') $family = $this->FontFamily;
        else $family = strtolower($family);
        if ($family=='arial') $family = 'helvetica';
        $style = strtoupper($style);
        if (strpos($style,'U')!==false) {
            $this->underline = true;
            $style = str_replace('U','',$style);
        } else {
            $this->underline = false;
        }
        if ($style=='IT') $style = 'I';
        if ($size==0) $size = $this->FontSizePt;

        if ($this->FontFamily==$family && $this->FontStyle==$style && $this->FontSizePt==$size) return;

        $fontkey = $family.$style;
        if (!isset($this->fonts[$fontkey])) {
            if (in_array($family, $this->CoreFonts)) {
                if ($family=='symbol' || $family=='zapfdingbats') $style = '';
                $fontkey = $family.$style;
                if (!isset($this->fonts[$fontkey]))
                    $this->_loadfont($family, $style);
            } else {
                $this->Error('Undefined font: '.$family.' '.$style);
            }
        }

        $this->FontFamily = $family;
        $this->FontStyle = $style;
        $this->FontSizePt = $size;
        $this->FontSize = $size / $this->k;
        $this->CurrentFont = &$this->fonts[$fontkey];
        if ($this->page > 0)
            $this->_out(sprintf('BT /F%d %.2F Tf ET', $this->CurrentFont['i'], $this->FontSizePt));
    }

    public function AddPage($orientation='', $size='') {
        if ($this->state==0) $this->Open();
        $family = $this->FontFamily;
        $style = $this->FontStyle . ($this->underline ? 'U' : '');
        $fontsize = $this->FontSizePt;
        $lw = $this->LineWidth;
        $dc = $this->DrawColor;
        $fc = $this->FillColor;
        $tc = $this->TextColor;

        if ($this->page > 0) {
            $this->InFooter = true;
            $this->Footer();
            $this->InFooter = false;
            $this->_endpage();
        }

        $this->_beginpage($orientation, $size);
        $this->_out('2 j');
        $this->LineWidth = $lw;
        $this->_out(sprintf('%.2F w', $lw*$this->k));
        if ($family) $this->SetFont($family, $style, $fontsize);
        $this->DrawColor = $dc;
        if ($dc!='0 G') $this->_out($dc);
        $this->FillColor = $fc;
        if ($fc!='0 g') $this->_out($fc);
        $this->TextColor = $tc;

        $this->InHeader = true;
        $this->Header();
        $this->InHeader = false;
    }

    public function Header() {}
    public function Footer() {}
    public function PageNo() { return $this->page; }

    public function Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false) {
        $k = $this->k;
        if ($this->y + $h > $this->PageBreakTrigger && !$this->InHeader && !$this->InFooter && $this->AcceptPageBreak()) {
            $x = $this->x;
            $ws = $this->ws;
            if ($ws>0) { $this->ws = 0; $this->_out('0 Tw'); }
            $this->AddPage($this->CurOrientation, $this->CurPageSize);
            $this->x = $x;
            if ($ws>0) { $this->ws = $ws; $this->_out(sprintf('%.3F Tw', $ws*$k)); }
        }
        if ($w==0) $w = $this->w - $this->rMargin - $this->x;
        $s = '';
        if ($fill || $border==1) {
            if ($fill) $op = ($border==1) ? 'B' : 'f';
            else $op = 'S';
            $s = sprintf('%.2F %.2F %.2F %.2F re %s ', $this->x*$k, ($this->h-$this->y)*$k, $w*$k, -$h*$k, $op);
        }
        if (is_string($border)) {
            $x = $this->x;
            $y = $this->y;
            if (strpos($border,'L')!==false) $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', $x*$k, ($this->h-$y)*$k, $x*$k, ($this->h-($y+$h))*$k);
            if (strpos($border,'T')!==false) $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', $x*$k, ($this->h-$y)*$k, ($x+$w)*$k, ($this->h-$y)*$k);
            if (strpos($border,'R')!==false) $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', ($x+$w)*$k, ($this->h-$y)*$k, ($x+$w)*$k, ($this->h-($y+$h))*$k);
            if (strpos($border,'B')!==false) $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', $x*$k, ($this->h-($y+$h))*$k, ($x+$w)*$k, ($this->h-($y+$h))*$k);
        }
        if ($txt!=='') {
            if ($align=='R') $dx = $w - $this->cMargin - $this->GetStringWidth($txt);
            elseif ($align=='C') $dx = ($w - $this->GetStringWidth($txt))/2;
            else $dx = $this->cMargin;
            if ($this->ColorFlag) $s .= 'q '.$this->TextColor.' ';
            $txt2 = str_replace(')', '\\)', str_replace('(', '\\(', str_replace('\\', '\\\\', $txt)));
            $s .= sprintf('BT %.2F %.2F Td (%s) Tj ET', ($this->x+$dx)*$k, ($this->h-($this->y+.7*$h))*$k, $txt2);
            if ($this->ColorFlag) $s .= ' Q';
        }
        if ($s) $this->_out($s);
        $this->lasth = $h;
        if ($ln>0) {
            $this->y += $h;
            if ($ln==1) $this->x = $this->lMargin;
        } else {
            $this->x += $w;
        }
    }

    public function Output($dest='', $name='') {
        if ($this->state<3) $this->Close();
        if (empty($dest)) $dest = 'I';
        if (empty($name)) $name = 'document.pdf';
        
        switch (strtoupper($dest)) {
            case 'I':
                $this->_checkoutput();
                header('Content-Type: application/pdf');
                header('Content-Disposition: inline; filename="'.$name.'"');
                echo $this->buffer;
                break;
            case 'D':
                $this->_checkoutput();
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="'.$name.'"');
                echo $this->buffer;
                break;
            case 'F':
                $f = fopen($name, 'wb');
                if (!$f) $this->Error('Unable to create output file: '.$name);
                fwrite($f, $this->buffer, strlen($this->buffer));
                fclose($f);
                break;
            case 'S':
                return $this->buffer;
            default:
                $this->Error('Incorrect output destination: '.$dest);
        }
        return '';
    }

    protected function Open() { 
        $this->state = 1; 
        $this->_out('%PDF-1.3'); 
    }

    protected function Close() {
        if ($this->state==3) return;
        if ($this->page==0) $this->AddPage();
        $this->InFooter = true;
        $this->Footer();
        $this->InFooter = false;
        $this->_endpage();
        $this->_enddoc();
    }

    protected function AcceptPageBreak() { return $this->AutoPageBreak; }
    protected function Error($msg) { throw new Exception('FPDF error: '.$msg); }

    protected function _getpagesize($size) {
        if (is_string($size)) {
            $a = strtolower($size);
            if ($a=='a3') $a = array(841.89, 1190.55);
            elseif ($a=='a4') $a = array(595.28, 841.89);
            elseif ($a=='a5') $a = array(420.94, 595.28);
            elseif ($a=='letter') $a = array(612, 792);
            elseif ($a=='legal') $a = array(612, 1008);
            else $this->Error('Unknown page size: '.$size);
            return array($a[0]/$this->k, $a[1]/$this->k);
        } else {
            return array($size[0]/$this->k, $size[1]/$this->k);
        }
    }

    protected function _beginpage($orientation, $size) {
        $this->page++;
        $this->pages[$this->page] = '';
        $this->state = 2;
        $this->x = $this->lMargin;
        $this->y = $this->tMargin;
        $this->FontFamily = '';
        if ($orientation=='') $orientation = $this->DefOrientation;
        if ($size=='') $size = $this->DefPageSize;
        else $size = $this->_getpagesize($size);

        if ($orientation!=$this->CurOrientation || $size[0]!=$this->CurPageSize[0] || $size[1]!=$this->CurPageSize[1]) {
            if ($orientation=='P') {
                $this->w = $size[0];
                $this->h = $size[1];
            } else {
                $this->w = $size[1];
                $this->h = $size[0];
            }
            $this->wPt = $this->w*$this->k;
            $this->hPt = $this->h*$this->k;
            $this->PageBreakTrigger = $this->h - $this->bMargin;
            $this->CurOrientation = $orientation;
            $this->CurPageSize = $size;
        }
    }

    protected function _endpage() { $this->state = 1; }

    protected function _loadfont($font, $style) {
        $fontkey = $font.$style;
        $cw = array(
            ' '=>278,'!'=>278,'"'=>355,'#'=>556,'$'=>556,'%'=>889,'&'=>667,'\''=>191,'('=>333,')'=>333,
            '*'=>389,'+'=>584,','=>278,'-'=>333,'.'=>278,'/'=>278,'0'=>556,'1'=>556,'2'=>556,'3'=>556,
            '4'=>556,'5'=>556,'6'=>556,'7'=>556,'8'=>556,'9'=>556,':'=>278,';'=>278,'<'=>584,'='=>584,
            '>'=>584,'?'=>556,'@'=>1015,'A'=>667,'B'=>667,'C'=>722,'D'=>722,'E'=>667,'F'=>611,'G'=>778,
            'H'=>722,'I'=>278,'J'=>500,'K'=>667,'L'=>556,'M'=>833,'N'=>722,'O'=>778,'P'=>667,'Q'=>778,
            'R'=>722,'S'=>667,'T'=>667,'U'=>722,'V'=>667,'W'=>944,'X'=>667,'Y'=>667,'Z'=>611,'['=>278,
            '\\'=>278,']'=>278,'^'=>469,'_'=>556,'`'=>333,'a'=>556,'b'=>556,'c'=>500,'d'=>556,'e'=>556,
            'f'=>278,'g'=>556,'h'=>556,'i'=>222,'j'=>222,'k'=>500,'l'=>222,'m'=>833,'n'=>556,'o'=>556,
            'p'=>556,'q'=>556,'r'=>333,'s'=>500,'t'=>278,'u'=>556,'v'=>500,'w'=>722,'x'=>500,'y'=>500,
            'z'=>500,'{'=>334,'|'=>260,'}'=>334,'~'=>584
        );
        $this->fonts[$fontkey] = array('i'=>count($this->fonts)+1, 'type'=>'core', 'name'=>$font, 'cw'=>$cw);
    }

    protected function _out($s) {
        if ($this->state==2) $this->pages[$this->page] .= $s."\n";
        else $this->buffer .= $s."\n";
    }

    protected function _enddoc() {
        $this->state = 3;
        $nb = $this->page;
        $this->offsets = array();

        // Pages & Stream Objects
        for ($n=1; $n<=$nb; $n++) {
            $pageObj = $n * 2;
            $streamObj = $n * 2 + 1;

            $this->offsets[$pageObj] = strlen($this->buffer);
            $this->_out($pageObj . ' 0 obj');
            $this->_out('<</Type /Page /Parent 1 0 R /MediaBox [0 0 '.sprintf('%.2F %.2F', $this->wPt, $this->hPt).'] /Contents '.$streamObj.' 0 R /Resources < /Font <');
            foreach ($this->fonts as $k=>$font) {
                $this->_out('/F'.$font['i'].' '.($nb*2+1+$font['i']).' 0 R ');
            }
            $this->_out('>> >> >>');
            $this->_out('endobj');

            $p = $this->pages[$n];
            $this->offsets[$streamObj] = strlen($this->buffer);
            $this->_out($streamObj . ' 0 obj');
            $this->_out('<</Length '.strlen($p).'>>');
            $this->_out('stream');
            $this->_out($p);
            $this->_out('endstream');
            $this->_out('endobj');
        }

        // Fonts
        foreach ($this->fonts as $k=>$font) {
            $objId = $nb*2 + 1 + $font['i'];
            $this->offsets[$objId] = strlen($this->buffer);
            $this->_out($objId . ' 0 obj');
            $this->_out('<</Type /Font /Subtype /Type1 /BaseFont /'.ucfirst($font['name']).' /Encoding /WinAnsiEncoding>>');
            $this->_out('endobj');
        }

        // Object 1: Pages root
        $this->offsets[1] = strlen($this->buffer);
        $this->_out('1 0 obj');
        $this->_out('<</Type /Pages /Count '.$nb.' /Kids [');
        for ($i=1; $i<=$nb; $i++) {
            $this->_out(($i*2).' 0 R ');
        }
        $this->_out(']>>');
        $this->_out('endobj');

        // Object 2: Catalog
        $this->offsets[2] = strlen($this->buffer);
        $this->_out('2 0 obj');
        $this->_out('<</Type /Catalog /Pages 1 0 R>>');
        $this->_out('endobj');

        // Cross-reference table
        $o = strlen($this->buffer);
        $this->_out('xref');
        $maxObj = max(array_keys($this->offsets));
        $this->_out('0 '.($maxObj + 1));
        $this->_out('0000000000 65535 f ');
        for ($i=1; $i<=$maxObj; $i++) {
            if (isset($this->offsets[$i])) {
                $this->_out(sprintf('%010d 00000 n ', $this->offsets[$i]));
            } else {
                $this->_out('0000000000 00000 f ');
            }
        }

        // Trailer
        $this->_out('trailer');
        $this->_out('<</Size '.($maxObj + 1).' /Root 2 0 R>>');
        $this->_out('startxref');
        $this->_out($o);
        $this->_out('%%EOF');
    }

    protected function _checkoutput() {
        if (PHP_SAPI!='cli') {
            if (headers_sent($file, $line)) {
                $this->Error("Some data has already been output, can't send PDF file (output started at $file:$line)");
            }
        }
    }
}
