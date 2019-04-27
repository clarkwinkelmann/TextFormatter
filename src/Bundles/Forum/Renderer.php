<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Bundles\Forum;

class Renderer extends \s9e\TextFormatter\Renderers\PHP
{
	protected $params=['L_HIDE'=>'Hide','L_SHOW'=>'Show','L_SPOILER'=>'Spoiler','L_WROTE'=>'wrote:'];
	protected function renderNode(\DOMNode $node)
	{
		switch($node->nodeName){case'B':$this->out.='<b>';$this->at($node);$this->out.='</b>';break;case'BANDCAMP':$this->out.='<span data-s9e-mediaembed="bandcamp" style="display:inline-block;width:100%;max-width:400px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:100%"><iframe allowfullscreen="" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//bandcamp.com/EmbeddedPlayer/size=large/minimal=true/';if($node->hasAttribute('album_id')){$this->out.='album='.htmlspecialchars($node->getAttribute('album_id'),2);if($node->hasAttribute('track_num'))$this->out.='/t='.htmlspecialchars($node->getAttribute('track_num'),2);}else$this->out.='track='.htmlspecialchars($node->getAttribute('track_id'),2);$this->out.='"></iframe></span></span>';break;case'CENTER':$this->out.='<div style="text-align:center">';$this->at($node);$this->out.='</div>';break;case'CODE':$this->out.='<pre data-hljs=""><code';if($node->hasAttribute('lang'))$this->out.=' class="language-'.htmlspecialchars($node->getAttribute('lang'),2).'"';$this->out.='>';$this->at($node);$this->out.='</code></pre><script>if("undefined"!==typeof hljs)hljs._ha();else if("undefined"===typeof hljsLoading){hljsLoading=1;var a=document.getElementsByTagName("head")[0],e=document.createElement("link");e.type="text/css";e.rel="stylesheet";e.href="//cdnjs.cloudflare.com/ajax/libs/highlight.js/9.7.0/styles/default.min.css";a.appendChild(e);e=document.createElement("script");e.type="text/javascript";e.onload=function(){var d={},f=0;hljs._hb=function(b){b.removeAttribute("data-hljs");var c=b.innerHTML;c in d?b.innerHTML=d[c]:(7<++f&&(d={},f=0),hljs.highlightBlock(b.firstChild),d[c]=b.innerHTML)};hljs._ha=function(){for(var b=document.querySelectorAll("pre[data-hljs]"),c=b.length;0<c;)hljs._hb(b.item(--c))};hljs._ha()};e.async=!0;e.src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/9.7.0/highlight.min.js";a.appendChild(e)}</script>';break;case'COLOR':$this->out.='<span style="color:'.htmlspecialchars($node->getAttribute('color'),2).'">';$this->at($node);$this->out.='</span>';break;case'DAILYMOTION':$this->out.='<span data-s9e-mediaembed="dailymotion" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//www.dailymotion.com/embed/video/'.htmlspecialchars($node->getAttribute('id'),2);if($node->hasAttribute('t'))$this->out.='?start='.htmlspecialchars($node->getAttribute('t'),2);$this->out.='"></iframe></span></span>';break;case'EMAIL':$this->out.='<a href="mailto:'.htmlspecialchars($node->getAttribute('email'),2).'">';$this->at($node);$this->out.='</a>';break;case'EMOJI':$this->out.='<img alt="'.htmlspecialchars($node->textContent,2).'" class="emoji" draggable="false" src="https://twemoji.maxcdn.com/2/svg/'.htmlspecialchars($node->getAttribute('tseq'),2).'.svg">';break;case'FACEBOOK':$this->out.='<iframe data-s9e-mediaembed="facebook" allowfullscreen="" onload="var c=new MessageChannel;c.port1.onmessage=function(e){style.height=e.data+\'px\'};contentWindow.postMessage(\'s9e:init\',\'https://s9e.github.io\',[c.port2])" scrolling="no" src="https://s9e.github.io/iframe/2/facebook.min.html#'.htmlspecialchars($node->getAttribute('type').$node->getAttribute('id'),2).'" style="border:0;height:360px;max-width:640px;width:100%"></iframe>';break;case'FONT':$this->out.='<span style="font-family:'.htmlspecialchars($node->getAttribute('font'),2).'">';$this->at($node);$this->out.='</span>';break;case'I':$this->out.='<i>';$this->at($node);$this->out.='</i>';break;case'IMG':$this->out.='<img src="'.htmlspecialchars($node->getAttribute('src'),2).'" title="'.htmlspecialchars($node->getAttribute('title'),2).'" alt="'.htmlspecialchars($node->getAttribute('alt'),2).'"';if($node->hasAttribute('height'))$this->out.=' height="'.htmlspecialchars($node->getAttribute('height'),2).'"';if($node->hasAttribute('width'))$this->out.=' width="'.htmlspecialchars($node->getAttribute('width'),2).'"';$this->out.='>';break;case'INDIEGOGO':$this->out.='<span data-s9e-mediaembed="indiegogo" style="display:inline-block;width:100%;max-width:222px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:200.45045%"><iframe allowfullscreen="" scrolling="no" src="//www.indiegogo.com/project/'.htmlspecialchars($node->getAttribute('id'),2).'/embedded" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span></span>';break;case'INSTAGRAM':$this->out.='<iframe data-s9e-mediaembed="instagram" allowfullscreen="" onload="var c=new MessageChannel;c.port1.onmessage=function(e){style.height=e.data+\'px\'};contentWindow.postMessage(\'s9e:init\',\'https://s9e.github.io\',[c.port2])" scrolling="no" src="https://s9e.github.io/iframe/2/instagram.min.html#'.htmlspecialchars($node->getAttribute('id'),2).'" style="background:url(https://www.instagram.com/static/images/ico/favicon.svg/fc72dd4bfde8.svg) no-repeat 50% 50% / 50%;border:0;height:540px;max-width:540px;width:100%"></iframe>';break;case'KICKSTARTER':$this->out.='<span data-s9e-mediaembed="kickstarter"';if($node->hasAttribute('video'))$this->out.=' style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" src="//www.kickstarter.com/projects/'.htmlspecialchars($node->getAttribute('id'),2).'/widget/video.html" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span>';else$this->out.=' style="display:inline-block;width:100%;max-width:220px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:190.909091%"><iframe allowfullscreen="" scrolling="no" src="//www.kickstarter.com/projects/'.htmlspecialchars($node->getAttribute('id'),2).'/widget/card.html" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span>';$this->out.='</span>';break;case'LI':$this->out.='<li>';$this->at($node);$this->out.='</li>';break;case'LIST':if(!$node->hasAttribute('type')){$this->out.='<ul>';$this->at($node);$this->out.='</ul>';}elseif((strpos($node->getAttribute('type'),'decimal')===0)||(strpos($node->getAttribute('type'),'lower')===0)||(strpos($node->getAttribute('type'),'upper')===0)){$this->out.='<ol style="list-style-type:'.htmlspecialchars($node->getAttribute('type'),2).'"';if($node->hasAttribute('start'))$this->out.=' start="'.htmlspecialchars($node->getAttribute('start'),2).'"';$this->out.='>';$this->at($node);$this->out.='</ol>';}else{$this->out.='<ul style="list-style-type:'.htmlspecialchars($node->getAttribute('type'),2).'">';$this->at($node);$this->out.='</ul>';}break;case'LIVELEAK':$this->out.='<span data-s9e-mediaembed="liveleak" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" src="//www.liveleak.com/e/'.htmlspecialchars($node->getAttribute('id'),2).'" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span></span>';break;case'OL':$this->out.='<ol>';$this->at($node);$this->out.='</ol>';break;case'QUOTE':$this->out.='<blockquote';if(!$node->hasAttribute('author'))$this->out.=' class="uncited"';$this->out.='><div>';if($node->hasAttribute('author'))$this->out.='<cite>'.htmlspecialchars($node->getAttribute('author'),0).' '.htmlspecialchars($this->params['L_WROTE'],0).'</cite>';$this->at($node);$this->out.='</div></blockquote>';break;case'S':$this->out.='<s>';$this->at($node);$this->out.='</s>';break;case'SIZE':$this->out.='<span style="font-size:'.htmlspecialchars($node->getAttribute('size'),2).'px">';$this->at($node);$this->out.='</span>';break;case'SOUNDCLOUD':$this->out.='<iframe data-s9e-mediaembed="soundcloud" allowfullscreen="" scrolling="no" src="https://w.soundcloud.com/player/?url=';if($node->hasAttribute('playlist_id'))$this->out.='https%3A//api.soundcloud.com/playlists/'.htmlspecialchars($node->getAttribute('playlist_id'),2);elseif($node->hasAttribute('track_id'))$this->out.='https%3A//api.soundcloud.com/tracks/'.htmlspecialchars($node->getAttribute('track_id'),2).'&amp;secret_token='.htmlspecialchars($node->getAttribute('secret_token'),2);else{if((strpos($node->getAttribute('id'),'://')===false))$this->out.='https%3A//soundcloud.com/';$this->out.=htmlspecialchars($node->getAttribute('id'),2);}$this->out.='" style="border:0;height:';if($node->hasAttribute('playlist_id')||(strpos($node->getAttribute('id'),'/sets/')!==false))$this->out.='450';else$this->out.='166';$this->out.='px;max-width:900px;width:100%"></iframe>';break;case'SPOILER':$this->out.='<div class="spoiler"><div class="spoiler-header"><button onclick="var a=parentNode.nextSibling.style,b=firstChild.style,c=lastChild.style;b.display=a.display;a.display=c.display=(b.display)?\'\':\'none\';return!1"><span>'.htmlspecialchars($this->params['L_SHOW'],0).'</span><span style="display:none">'.htmlspecialchars($this->params['L_HIDE'],0).'</span></button><span class="spoiler-title">'.htmlspecialchars($this->params['L_SPOILER'],0).' '.htmlspecialchars($node->getAttribute('title'),0).'</span></div><div class="spoiler-content" style="display:none">';$this->at($node);$this->out.='</div></div>';break;case'TABLE':$this->out.='<table>';$this->at($node);$this->out.='</table>';break;case'TD':$this->out.='<td';if($node->hasAttribute('colspan'))$this->out.=' colspan="'.htmlspecialchars($node->getAttribute('colspan'),2).'"';if($node->hasAttribute('rowspan'))$this->out.=' rowspan="'.htmlspecialchars($node->getAttribute('rowspan'),2).'"';if($node->hasAttribute('align'))$this->out.=' style="text-align:'.htmlspecialchars($node->getAttribute('align'),2).'"';$this->out.='>';$this->at($node);$this->out.='</td>';break;case'TH':$this->out.='<th';if($node->hasAttribute('colspan'))$this->out.=' colspan="'.htmlspecialchars($node->getAttribute('colspan'),2).'"';if($node->hasAttribute('rowspan'))$this->out.=' rowspan="'.htmlspecialchars($node->getAttribute('rowspan'),2).'"';if($node->hasAttribute('align'))$this->out.=' style="text-align:'.htmlspecialchars($node->getAttribute('align'),2).'"';$this->out.='>';$this->at($node);$this->out.='</th>';break;case'TR':$this->out.='<tr>';$this->at($node);$this->out.='</tr>';break;case'TWITCH':$this->out.='<span data-s9e-mediaembed="twitch" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//';if($node->hasAttribute('clip_id')){$this->out.='clips.twitch.tv/embed?autoplay=false&amp;clip=';if($node->hasAttribute('channel'))$this->out.=htmlspecialchars($node->getAttribute('channel'),2).'/';$this->out.=htmlspecialchars($node->getAttribute('clip_id'),2);}else{$this->out.='player.twitch.tv/?autoplay=false&amp;';if($node->hasAttribute('video_id'))$this->out.='video=v'.htmlspecialchars($node->getAttribute('video_id'),2);else$this->out.='channel='.htmlspecialchars($node->getAttribute('channel'),2);if($node->hasAttribute('t'))$this->out.='&amp;time='.htmlspecialchars($node->getAttribute('t'),2);}$this->out.='"></iframe></span></span>';break;case'TWITTER':$this->out.='<iframe data-s9e-mediaembed="twitter" allow="autoplay *" allowfullscreen="" onload="var c=new MessageChannel;c.port1.onmessage=function(e){style.height=e.data+\'px\'};contentWindow.postMessage(\'s9e:init\',\'https://s9e.github.io\',[c.port2])" scrolling="no" src="https://s9e.github.io/iframe/2/twitter.min.html#'.htmlspecialchars($node->getAttribute('id'),2).'" style="background:url(https://abs.twimg.com/favicons/favicon.ico) no-repeat 50% 50%;border:0;height:250px;max-width:500px;width:100%"></iframe>';break;case'U':$this->out.='<u>';$this->at($node);$this->out.='</u>';break;case'UL':$this->out.='<ul>';$this->at($node);$this->out.='</ul>';break;case'URL':$this->out.='<a href="'.htmlspecialchars($node->getAttribute('url'),2).'"';if($node->hasAttribute('title'))$this->out.=' title="'.htmlspecialchars($node->getAttribute('title'),2).'"';$this->out.='>';$this->at($node);$this->out.='</a>';break;case'VIMEO':$this->out.='<span data-s9e-mediaembed="vimeo" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//player.vimeo.com/video/'.htmlspecialchars($node->getAttribute('id'),2);if($node->hasAttribute('t'))$this->out.='#t='.htmlspecialchars($node->getAttribute('t'),2);$this->out.='"></iframe></span></span>';break;case'VINE':$this->out.='<span data-s9e-mediaembed="vine" style="display:inline-block;width:100%;max-width:480px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:100%"><iframe allowfullscreen="" scrolling="no" src="https://vine.co/v/'.htmlspecialchars($node->getAttribute('id'),2).'/embed/simple?audio=1" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span></span>';break;case'WSHH':$this->out.='<span data-s9e-mediaembed="wshh" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" src="//www.worldstarhiphop.com/embed/'.htmlspecialchars($node->getAttribute('id'),2).'" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span></span>';break;case'YOUTUBE':$this->out.='<span data-s9e-mediaembed="youtube" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="background:url(https://i.ytimg.com/vi/'.htmlspecialchars($node->getAttribute('id'),2).'/hqdefault.jpg) 50% 50% / cover;border:0;height:100%;left:0;position:absolute;width:100%" src="https://www.youtube.com/embed/'.htmlspecialchars($node->getAttribute('id'),2);if($node->hasAttribute('list'))$this->out.='?list='.htmlspecialchars($node->getAttribute('list'),2);if($node->hasAttribute('t')){if($node->hasAttribute('list'))$this->out.='&amp;';else$this->out.='?';$this->out.='start='.htmlspecialchars($node->getAttribute('t'),2);}$this->out.='"></iframe></span></span>';break;case'br':$this->out.='<br>';break;case'e':case'i':case's':break;case'p':$this->out.='<p>';$this->at($node);$this->out.='</p>';break;default:$this->at($node);}
	}
	/** {@inheritdoc} */
	public $enableQuickRenderer=true;
	/** {@inheritdoc} */
	protected $static=['/B'=>'</b>','/CENTER'=>'</div>','/CODE'=>'</code></pre><script>if("undefined"!==typeof hljs)hljs._ha();else if("undefined"===typeof hljsLoading){hljsLoading=1;var a=document.getElementsByTagName("head")[0],e=document.createElement("link");e.type="text/css";e.rel="stylesheet";e.href="//cdnjs.cloudflare.com/ajax/libs/highlight.js/9.7.0/styles/default.min.css";a.appendChild(e);e=document.createElement("script");e.type="text/javascript";e.onload=function(){var d={},f=0;hljs._hb=function(b){b.removeAttribute("data-hljs");var c=b.innerHTML;c in d?b.innerHTML=d[c]:(7<++f&&(d={},f=0),hljs.highlightBlock(b.firstChild),d[c]=b.innerHTML)};hljs._ha=function(){for(var b=document.querySelectorAll("pre[data-hljs]"),c=b.length;0<c;)hljs._hb(b.item(--c))};hljs._ha()};e.async=!0;e.src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/9.7.0/highlight.min.js";a.appendChild(e)}</script>','/COLOR'=>'</span>','/EMAIL'=>'</a>','/FONT'=>'</span>','/I'=>'</i>','/LI'=>'</li>','/OL'=>'</ol>','/QUOTE'=>'</div></blockquote>','/S'=>'</s>','/SIZE'=>'</span>','/SPOILER'=>'</div></div>','/TABLE'=>'</table>','/TD'=>'</td>','/TH'=>'</th>','/TR'=>'</tr>','/U'=>'</u>','/UL'=>'</ul>','/URL'=>'</a>','B'=>'<b>','CENTER'=>'<div style="text-align:center">','I'=>'<i>','LI'=>'<li>','OL'=>'<ol>','S'=>'<s>','TABLE'=>'<table>','TR'=>'<tr>','U'=>'<u>','UL'=>'<ul>'];
	/** {@inheritdoc} */
	protected $dynamic=['COLOR'=>['(^[^ ]+(?> (?!color=)[^=]+="[^"]*")*(?> color="([^"]*)")?.*)s','<span style="color:$1">'],'EMAIL'=>['(^[^ ]+(?> (?!email=)[^=]+="[^"]*")*(?> email="([^"]*)")?.*)s','<a href="mailto:$1">'],'FONT'=>['(^[^ ]+(?> (?!font=)[^=]+="[^"]*")*(?> font="([^"]*)")?.*)s','<span style="font-family:$1">'],'IMG'=>['(^[^ ]+(?> (?!(?:alt|height|src|title|width)=)[^=]+="[^"]*")*(?> alt="([^"]*)")?(?> (?!(?:height|src|title|width)=)[^=]+="[^"]*")*( height="[^"]*")?(?> (?!(?:src|title|width)=)[^=]+="[^"]*")*(?> src="([^"]*)")?(?> (?!(?:title|width)=)[^=]+="[^"]*")*(?> title="([^"]*)")?(?> (?!width=)[^=]+="[^"]*")*( width="[^"]*")?.*)s','<img src="$3" title="$4" alt="$1"$2$5>'],'INDIEGOGO'=>['(^[^ ]+(?> (?!id=)[^=]+="[^"]*")*(?> id="([^"]*)")?.*)s','<span data-s9e-mediaembed="indiegogo" style="display:inline-block;width:100%;max-width:222px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:200.45045%"><iframe allowfullscreen="" scrolling="no" src="//www.indiegogo.com/project/$1/embedded" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span></span>'],'INSTAGRAM'=>['(^[^ ]+(?> (?!id=)[^=]+="[^"]*")*(?> id="([^"]*)")?.*)s','<iframe data-s9e-mediaembed="instagram" allowfullscreen="" onload="var c=new MessageChannel;c.port1.onmessage=function(e){style.height=e.data+\'px\'};contentWindow.postMessage(\'s9e:init\',\'https://s9e.github.io\',[c.port2])" scrolling="no" src="https://s9e.github.io/iframe/2/instagram.min.html#$1" style="background:url(https://www.instagram.com/static/images/ico/favicon.svg/fc72dd4bfde8.svg) no-repeat 50% 50% / 50%;border:0;height:540px;max-width:540px;width:100%"></iframe>'],'LIVELEAK'=>['(^[^ ]+(?> (?!id=)[^=]+="[^"]*")*(?> id="([^"]*)")?.*)s','<span data-s9e-mediaembed="liveleak" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" src="//www.liveleak.com/e/$1" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span></span>'],'SIZE'=>['(^[^ ]+(?> (?!size=)[^=]+="[^"]*")*(?> size="([^"]*)")?.*)s','<span style="font-size:$1px">'],'TWITTER'=>['(^[^ ]+(?> (?!id=)[^=]+="[^"]*")*(?> id="([^"]*)")?.*)s','<iframe data-s9e-mediaembed="twitter" allow="autoplay *" allowfullscreen="" onload="var c=new MessageChannel;c.port1.onmessage=function(e){style.height=e.data+\'px\'};contentWindow.postMessage(\'s9e:init\',\'https://s9e.github.io\',[c.port2])" scrolling="no" src="https://s9e.github.io/iframe/2/twitter.min.html#$1" style="background:url(https://abs.twimg.com/favicons/favicon.ico) no-repeat 50% 50%;border:0;height:250px;max-width:500px;width:100%"></iframe>'],'URL'=>['(^[^ ]+(?> (?!(?:title|url)=)[^=]+="[^"]*")*( title="[^"]*")?(?> (?!url=)[^=]+="[^"]*")*(?> url="([^"]*)")?.*)s','<a href="$2"$1>'],'VINE'=>['(^[^ ]+(?> (?!id=)[^=]+="[^"]*")*(?> id="([^"]*)")?.*)s','<span data-s9e-mediaembed="vine" style="display:inline-block;width:100%;max-width:480px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:100%"><iframe allowfullscreen="" scrolling="no" src="https://vine.co/v/$1/embed/simple?audio=1" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span></span>'],'WSHH'=>['(^[^ ]+(?> (?!id=)[^=]+="[^"]*")*(?> id="([^"]*)")?.*)s','<span data-s9e-mediaembed="wshh" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" src="//www.worldstarhiphop.com/embed/$1" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span></span>']];
	/** {@inheritdoc} */
	protected $quickRegexp='(<(?:(?!/)((?:BANDCAMP|DAILYMOTION|EMOJI|FACEBOOK|I(?:MG|N(?:DIEGOGO|STAGRAM))|KICKSTARTER|LIVELEAK|SOUNDCLOUD|TWIT(?:CH|TER)|VI(?:MEO|NE)|WSHH|YOUTUBE))(?: [^>]*)?>.*?</\\1|(/?(?!br/|p>)[^ />]+)[^>]*?(/)?)>)s';
	/** {@inheritdoc} */
	protected function renderQuickTemplate($id, $xml)
	{
		$attributes=$this->matchAttributes($xml);
		$html='';switch($id){case'/LIST':$attributes=array_pop($this->attributes);if(!isset($attributes['type']))$html.='</ul>';elseif((strpos($attributes['type'],'decimal')===0)||(strpos($attributes['type'],'lower')===0)||(strpos($attributes['type'],'upper')===0))$html.='</ol>';else$html.='</ul>';break;case'BANDCAMP':$attributes+=['track_num'=>null,'track_id'=>null];$html.='<span data-s9e-mediaembed="bandcamp" style="display:inline-block;width:100%;max-width:400px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:100%"><iframe allowfullscreen="" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//bandcamp.com/EmbeddedPlayer/size=large/minimal=true/';if(isset($attributes['album_id'])){$html.='album='.$attributes['album_id'];if(isset($attributes['track_num']))$html.='/t='.$attributes['track_num'];}else$html.='track='.$attributes['track_id'];$html.='"></iframe></span></span>';break;case'CODE':$html.='<pre data-hljs=""><code';if(isset($attributes['lang']))$html.=' class="language-'.$attributes['lang'].'"';$html.='>';break;case'DAILYMOTION':$attributes+=['id'=>null];$html.='<span data-s9e-mediaembed="dailymotion" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//www.dailymotion.com/embed/video/'.$attributes['id'];if(isset($attributes['t']))$html.='?start='.$attributes['t'];$html.='"></iframe></span></span>';break;case'EMOJI':$attributes+=['tseq'=>null];$textContent=$this->getQuickTextContent($xml);$html.='<img alt="'.htmlspecialchars($textContent,2).'" class="emoji" draggable="false" src="https://twemoji.maxcdn.com/2/svg/'.$attributes['tseq'].'.svg">';break;case'FACEBOOK':$attributes+=['type'=>null,'id'=>null];$html.='<iframe data-s9e-mediaembed="facebook" allowfullscreen="" onload="var c=new MessageChannel;c.port1.onmessage=function(e){style.height=e.data+\'px\'};contentWindow.postMessage(\'s9e:init\',\'https://s9e.github.io\',[c.port2])" scrolling="no" src="https://s9e.github.io/iframe/2/facebook.min.html#'.$attributes['type'].$attributes['id'].'" style="border:0;height:360px;max-width:640px;width:100%"></iframe>';break;case'KICKSTARTER':$attributes+=['id'=>null];$html.='<span data-s9e-mediaembed="kickstarter"';if(isset($attributes['video']))$html.=' style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" src="//www.kickstarter.com/projects/'.$attributes['id'].'/widget/video.html" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span>';else$html.=' style="display:inline-block;width:100%;max-width:220px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:190.909091%"><iframe allowfullscreen="" scrolling="no" src="//www.kickstarter.com/projects/'.$attributes['id'].'/widget/card.html" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span>';$html.='</span>';break;case'LIST':$attributes+=['type'=>null];if(!isset($attributes['type']))$html.='<ul>';elseif((strpos($attributes['type'],'decimal')===0)||(strpos($attributes['type'],'lower')===0)||(strpos($attributes['type'],'upper')===0)){$html.='<ol style="list-style-type:'.$attributes['type'].'"';if(isset($attributes['start']))$html.=' start="'.$attributes['start'].'"';$html.='>';}else$html.='<ul style="list-style-type:'.$attributes['type'].'">';$this->attributes[]=$attributes;break;case'QUOTE':$html.='<blockquote';if(!isset($attributes['author']))$html.=' class="uncited"';$html.='><div>';if(isset($attributes['author']))$html.='<cite>'.str_replace('&quot;','"',$attributes['author']).' '.htmlspecialchars($this->params['L_WROTE'],0).'</cite>';break;case'SOUNDCLOUD':$attributes+=['secret_token'=>null,'id'=>null];$html.='<iframe data-s9e-mediaembed="soundcloud" allowfullscreen="" scrolling="no" src="https://w.soundcloud.com/player/?url=';if(isset($attributes['playlist_id']))$html.='https%3A//api.soundcloud.com/playlists/'.$attributes['playlist_id'];elseif(isset($attributes['track_id']))$html.='https%3A//api.soundcloud.com/tracks/'.$attributes['track_id'].'&amp;secret_token='.$attributes['secret_token'];else{if((strpos($attributes['id'],'://')===false))$html.='https%3A//soundcloud.com/';$html.=$attributes['id'];}$html.='" style="border:0;height:';if(isset($attributes['playlist_id'])||(strpos($attributes['id'],'/sets/')!==false))$html.='450';else$html.='166';$html.='px;max-width:900px;width:100%"></iframe>';break;case'SPOILER':$attributes+=['title'=>null];$html.='<div class="spoiler"><div class="spoiler-header"><button onclick="var a=parentNode.nextSibling.style,b=firstChild.style,c=lastChild.style;b.display=a.display;a.display=c.display=(b.display)?\'\':\'none\';return!1"><span>'.htmlspecialchars($this->params['L_SHOW'],0).'</span><span style="display:none">'.htmlspecialchars($this->params['L_HIDE'],0).'</span></button><span class="spoiler-title">'.htmlspecialchars($this->params['L_SPOILER'],0).' '.str_replace('&quot;','"',$attributes['title']).'</span></div><div class="spoiler-content" style="display:none">';break;case'TD':$html.='<td';if(isset($attributes['colspan']))$html.=' colspan="'.$attributes['colspan'].'"';if(isset($attributes['rowspan']))$html.=' rowspan="'.$attributes['rowspan'].'"';if(isset($attributes['align']))$html.=' style="text-align:'.$attributes['align'].'"';$html.='>';break;case'TH':$html.='<th';if(isset($attributes['colspan']))$html.=' colspan="'.$attributes['colspan'].'"';if(isset($attributes['rowspan']))$html.=' rowspan="'.$attributes['rowspan'].'"';if(isset($attributes['align']))$html.=' style="text-align:'.$attributes['align'].'"';$html.='>';break;case'TWITCH':$attributes+=['channel'=>null,'clip_id'=>null];$html.='<span data-s9e-mediaembed="twitch" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//';if(isset($attributes['clip_id'])){$html.='clips.twitch.tv/embed?autoplay=false&amp;clip=';if(isset($attributes['channel']))$html.=$attributes['channel'].'/';$html.=$attributes['clip_id'];}else{$html.='player.twitch.tv/?autoplay=false&amp;';if(isset($attributes['video_id']))$html.='video=v'.$attributes['video_id'];else$html.='channel='.$attributes['channel'];if(isset($attributes['t']))$html.='&amp;time='.$attributes['t'];}$html.='"></iframe></span></span>';break;case'VIMEO':$attributes+=['id'=>null];$html.='<span data-s9e-mediaembed="vimeo" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//player.vimeo.com/video/'.$attributes['id'];if(isset($attributes['t']))$html.='#t='.$attributes['t'];$html.='"></iframe></span></span>';break;case'YOUTUBE':$attributes+=['id'=>null,'t'=>null];$html.='<span data-s9e-mediaembed="youtube" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="background:url(https://i.ytimg.com/vi/'.$attributes['id'].'/hqdefault.jpg) 50% 50% / cover;border:0;height:100%;left:0;position:absolute;width:100%" src="https://www.youtube.com/embed/'.$attributes['id'];if(isset($attributes['list']))$html.='?list='.$attributes['list'];if(isset($attributes['t'])){if(isset($attributes['list']))$html.='&amp;';else$html.='?';$html.='start='.$attributes['t'];}$html.='"></iframe></span></span>';}

		return $html;
	}
}