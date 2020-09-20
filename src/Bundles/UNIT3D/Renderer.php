<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Bundles\UNIT3D;

class Renderer extends \s9e\TextFormatter\Renderers\PHP
{
	protected $params=[];
	protected function renderNode(\DOMNode $node)
	{
		switch($node->nodeName){case'ALERT':$this->out.='<div class="bbcode-alert">';$this->at($node);$this->out.='</div>';break;case'B':$this->out.='<span style="font-weight:bold">';$this->at($node);$this->out.='</span>';break;case'CENTER':$this->out.='<div style="text-align:center">';$this->at($node);$this->out.='</div>';break;case'CODE':$this->out.='<pre>';$this->at($node);$this->out.='</pre>';break;case'COLOR':$this->out.='<span style="color:'.htmlspecialchars($node->getAttribute('color'),2).'">$2</span>';break;case'FONT':$this->out.='<span style="font-family:'.htmlspecialchars($node->getAttribute('font'),2).'">';$this->at($node);$this->out.='</span>';break;case'H1':$this->out.='<h1>';$this->at($node);$this->out.='</h1>';break;case'H2':$this->out.='<h2>';$this->at($node);$this->out.='</h2>';break;case'H3':$this->out.='<h3>';$this->at($node);$this->out.='</h3>';break;case'H4':$this->out.='<h4>';$this->at($node);$this->out.='</h4>';break;case'H5':$this->out.='<h5>';$this->at($node);$this->out.='</h5>';break;case'H6':$this->out.='<h6>';$this->at($node);$this->out.='</h6>';break;case'I':$this->out.='<em>';$this->at($node);$this->out.='</em>';break;case'IMG':$this->out.='<img src="'.htmlspecialchars($node->getAttribute('src'),2).'"';if($node->hasAttribute('width'))$this->out.=' width="'.htmlspecialchars($node->getAttribute('width'),2).'"';$this->out.='>';break;case'LEFT':$this->out.='<div style="text-align:left">';$this->at($node);$this->out.='</div>';break;case'LI':$this->out.='<li>';$this->at($node);$this->out.='</li>';break;case'LIST':switch($node->getAttribute('type')){case'a':$this->out.='<ol type="a">';$this->at($node);$this->out.='</ol>';break;case'1':$this->out.='<ol>';$this->at($node);$this->out.='</ol>';break;default:$this->out.='<ul>';$this->at($node);$this->out.='</ul>';}break;case'NOTE':$this->out.='<div class="bbcode-note">';$this->at($node);$this->out.='</div>';break;case'QUOTE':$this->out.='<ul class="media-list comments-list"><li class="media" style="border-left-width:5px;border-left-style:solid;border-left-color:rgb(1,188,140)"><div class="media-body">';if($node->hasAttribute('name'))$this->out.='<strong><span><i class="fas fa-quote-left"></i> Quoting $1 :</span></strong>';$this->out.='<div class="pt-5">';$this->at($node);$this->out.='</div></div></li></ul>';break;case'RIGHT':$this->out.='<div style="text-align:right">';$this->at($node);$this->out.='</div>';break;case'S':$this->out.='<span style="text-decoration:line-through">';$this->at($node);$this->out.='</span>';break;case'SIZE':$this->out.='<span style="font-size:'.htmlspecialchars($node->getAttribute('size'),2).'px">';$this->at($node);$this->out.='</span>';break;case'SMALL':$this->out.='<small>';$this->at($node);$this->out.='</small>';break;case'SPOILER':$this->out.='<details class="label label-primary"><summary>';if($node->hasAttribute('title'))$this->out.=htmlspecialchars($node->getAttribute('title'),0);else$this->out.='Spoiler';$this->out.='</summary><pre><code><div style="text-align:left">';$this->at($node);$this->out.='</div></code></pre></details>';break;case'SUB':$this->out.='<sub>';$this->at($node);$this->out.='</sub>';break;case'SUP':$this->out.='<sup>';$this->at($node);$this->out.='</sup>';break;case'TABLE':$this->out.='<table>';$this->at($node);$this->out.='</table>';break;case'TD':$this->out.='<td>';$this->at($node);$this->out.='</td>';break;case'TR':$this->out.='<tr>';$this->at($node);$this->out.='</tr>';break;case'U':$this->out.='<u>';$this->at($node);$this->out.='</u>';break;case'URL':$this->out.='<a href="'.htmlspecialchars($node->getAttribute('url'),2).'">';$this->at($node);$this->out.='</a>';break;case'YOUTUBE':$this->out.='<span data-s9e-mediaembed="youtube" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="background:url(https://i.ytimg.com/vi/'.htmlspecialchars($node->getAttribute('id'),2).'/hqdefault.jpg) 50% 50% / cover;border:0;height:100%;left:0;position:absolute;width:100%" src="https://www.youtube-nocookie.com/embed/'.htmlspecialchars($node->getAttribute('id'),2);if($node->hasAttribute('list'))$this->out.='?list='.htmlspecialchars($node->getAttribute('list'),2);if($node->hasAttribute('t')){if($node->hasAttribute('list'))$this->out.='&amp;';else$this->out.='?';$this->out.='start='.htmlspecialchars($node->getAttribute('t'),2);}$this->out.='"></iframe></span></span>';break;case'br':$this->out.='<br>';break;case'e':case'i':case's':break;case'p':$this->out.='<p>';$this->at($node);$this->out.='</p>';break;default:$this->at($node);}
	}
	/** {@inheritdoc} */
	public $enableQuickRenderer=true;
	/** {@inheritdoc} */
	protected $static=['/ALERT'=>'</div>','/B'=>'</span>','/CENTER'=>'</div>','/CODE'=>'</pre>','/FONT'=>'</span>','/H1'=>'</h1>','/H2'=>'</h2>','/H3'=>'</h3>','/H4'=>'</h4>','/H5'=>'</h5>','/H6'=>'</h6>','/I'=>'</em>','/LEFT'=>'</div>','/LI'=>'</li>','/NOTE'=>'</div>','/QUOTE'=>'</div></div></li></ul>','/RIGHT'=>'</div>','/S'=>'</span>','/SIZE'=>'</span>','/SMALL'=>'</small>','/SPOILER'=>'</div></code></pre></details>','/SUB'=>'</sub>','/SUP'=>'</sup>','/TABLE'=>'</table>','/TD'=>'</td>','/TR'=>'</tr>','/U'=>'</u>','/URL'=>'</a>','ALERT'=>'<div class="bbcode-alert">','B'=>'<span style="font-weight:bold">','CENTER'=>'<div style="text-align:center">','CODE'=>'<pre>','H1'=>'<h1>','H2'=>'<h2>','H3'=>'<h3>','H4'=>'<h4>','H5'=>'<h5>','H6'=>'<h6>','I'=>'<em>','LEFT'=>'<div style="text-align:left">','LI'=>'<li>','NOTE'=>'<div class="bbcode-note">','RIGHT'=>'<div style="text-align:right">','S'=>'<span style="text-decoration:line-through">','SMALL'=>'<small>','SUB'=>'<sub>','SUP'=>'<sup>','TABLE'=>'<table>','TD'=>'<td>','TR'=>'<tr>','U'=>'<u>'];
	/** {@inheritdoc} */
	protected $dynamic=['COLOR'=>['(^[^ ]+(?> (?!color=)[^=]+="[^"]*")*(?> color="([^"]*)")?.*)s','<span style="color:$1">\\$2</span>'],'FONT'=>['(^[^ ]+(?> (?!font=)[^=]+="[^"]*")*(?> font="([^"]*)")?.*)s','<span style="font-family:$1">'],'IMG'=>['(^[^ ]+(?> (?!(?:src|width)=)[^=]+="[^"]*")*(?> src="([^"]*)")?(?> (?!width=)[^=]+="[^"]*")*( width="[^"]*")?.*)s','<img src="$1"$2>'],'SIZE'=>['(^[^ ]+(?> (?!size=)[^=]+="[^"]*")*(?> size="([^"]*)")?.*)s','<span style="font-size:$1px">'],'URL'=>['(^[^ ]+(?> (?!url=)[^=]+="[^"]*")*(?> url="([^"]*)")?.*)s','<a href="$1">']];
	/** {@inheritdoc} */
	protected $quickRegexp='(<(?:(?!/)((?:COLOR|IMG|YOUTUBE))(?: [^>]*)?>.*?</\\1|(/?(?!br/|p>)[^ />]+)[^>]*?(/)?)>)s';
	/** {@inheritdoc} */
	protected $quickRenderingTest='((?<=<)(?:[!?]|LIST[ />]))';
	/** {@inheritdoc} */
	protected function renderQuickTemplate($id, $xml)
	{
		$attributes=$this->matchAttributes($xml);
		$html='';switch($id){case'QUOTE':$html.='<ul class="media-list comments-list"><li class="media" style="border-left-width:5px;border-left-style:solid;border-left-color:rgb(1,188,140)"><div class="media-body">';if(isset($attributes['name']))$html.='<strong><span><i class="fas fa-quote-left"></i> Quoting $1 :</span></strong>';$html.='<div class="pt-5">';break;case'SPOILER':$html.='<details class="label label-primary"><summary>';if(isset($attributes['title']))$html.=str_replace('&quot;','"',$attributes['title']);else$html.='Spoiler';$html.='</summary><pre><code><div style="text-align:left">';break;case'YOUTUBE':$attributes+=['id'=>null,'t'=>null];$html.='<span data-s9e-mediaembed="youtube" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="background:url(https://i.ytimg.com/vi/'.$attributes['id'].'/hqdefault.jpg) 50% 50% / cover;border:0;height:100%;left:0;position:absolute;width:100%" src="https://www.youtube-nocookie.com/embed/'.$attributes['id'];if(isset($attributes['list']))$html.='?list='.$attributes['list'];if(isset($attributes['t'])){if(isset($attributes['list']))$html.='&amp;';else$html.='?';$html.='start='.$attributes['t'];}$html.='"></iframe></span></span>';}

		return $html;
	}
}