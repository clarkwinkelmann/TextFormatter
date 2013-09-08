<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;

use DOMDocument;
use DOMNode;
use RuntimeException;

class XPathParser
{
	/**
	* Parse an XPath expression
	*
	* @link http://www.w3.org/TR/xpath/
	*
	* @param  string      $expr      XPath expression
	* @param  string      $tokenName Start token, e.g. "Expr" or "LocationPath"
	* @return DOMDocument
	*/
	public static function parse($expr, $tokenName = 'Expr')
	{
		$dom = new DOMDocument;

		self::appendExpr($dom, $expr, $tokenName);

		return $dom;
	}

	/**
	* Append the representation of given expression
	*
	* @param  DOMNode $node      Host node
	* @param  string  $expr      XPath expression
	* @param  string  $tokenName Start token
	* @return void
	*/
	protected static function appendExpr(DOMNode $node, $expr, $tokenName)
	{
		$regexp = self::$regexps[$tokenName];

		if (!preg_match($regexp, $expr, $matches, PREG_OFFSET_CAPTURE))
		{
			throw new RuntimeException("Cannot parse '" . $expr . "' as " . $tokenName);
		}

		// Create a node if the token name starts with a capital. Tokens from the specs are
		// capitalized, custom ones are in lowercase
		if (preg_match('#^[A-Z]#', $tokenName))
		{
			$doc  = ($node instanceof DOMDocument) ? $node : $node->ownerDocument;
			$node = $node->appendChild($doc->createElement($tokenName));
		}

		$pos = 0;
		foreach ($matches as $k => list($matchText, $matchPos))
		{
			if ($matchPos < $pos
			 || $matchText === ''
			 || is_numeric($k))
			{
				continue;
			}

			if ($matchPos !== $pos)
			{
				$node->appendChild(
					$node->ownerDocument->createTextNode(substr($expr, $pos, $matchPos - $pos))
				);
			}

			$tokenName = rtrim($k, '0123456789');
			self::appendExpr($node, $matchText, $tokenName);
			$pos = $matchPos + strlen($matchText);
		}

		if ($pos < strlen($expr))
		{
			$node->appendChild($node->ownerDocument->createTextNode(substr($expr, $pos)));
		}
	}

	/**
	* @var array Regexps used for matching tokens
	*
	* @see scripts/patchXPathParser.php
	*/
	protected static $regexps = [
		'AbbreviatedAbsoluteLocationPath' => '(^\\s*(?://\\s*(?<RelativeLocationPath0>(?<a>(?<b>(?:(?:ancestor|ancestor-or-self|attribute|child|descendant|descendant-or-self|following|following-sibling|namespace|parent|preceding|preceding-sibling|self)\\s*::|@?)\\s*(?:(?:\\*|(?<n>[-\\w]+)\\s*:\\s*\\*|(?<o>(?:(?&n)\\s*:\\s*(?<u>(?&n)))|(?&u)))|(?<k>comment|text|processing-instruction|node)\\s*\\(\\s*\\)|processing-instruction\\s*\\(\\s*(?<l>"[^"]*"|\'[^\']*\')\\s*\\))\\s*(?:(?<f>(?:\\[\\s*(?:(?<s>(?:(?<w>(?:(?<y>(?<z>(?<aa>(?<ab>(?:(?<ae>(?:(?:(?:\\$\\s*(?&o))|\\(\\s*(?&s)\\s*\\)|(?&l)|(?:(?<an>[0-9]+)\\s*(?:\\.\\s*(?&an)?)?|\\.\\s*(?&an))|(?:(?!(?&k))(?&o)\\s*\\(\\s*(?:(?<ap>(?&s)\\s*(?:,\\s*(?&ap))?))?\\s*\\)))\\s*(?&f)?)\\s*(?://?\\s*(?&a))?|(?:(?&a)|(?:/\\s*(?&a)?|(?://\\s*(?&a)))))|(?:(?:(?&ae)\\s*\\|)*\\s*(?&ae))\\s*\\|\\s*(?&ae))|-\\s*(?&ab))\\s*(?:(?:\\*|div|mod)\\s*(?&ab))?)\\s*(?:[-+]\\s*(?&aa))?)\\s*(?:[<>]=?\\s*(?&z))?)\\s*(?:!?=\\s*(?&y))?)\\s*(?:and\\s*(?&w))?)\\s*(?:or\\s*(?&w))?)))\\s*\\])\\s*(?&f)?))?|\\.\\.?)|(?:(?:(?&b)\\s*//?)*\\s*(?&b))\\s*/\\s*(?&b)|(?:(?:(?:(?&b)\\s*//?)*\\s*(?&b))\\s*//\\s*(?&b)))))\\s*$)',
		'AbbreviatedAxisSpecifier' => '(^\\s*@?\\s*$)',
		'AbbreviatedRelativeLocationPath' => '(^\\s*(?:(?<RelativeLocationPath0>(?:(?<Step0>(?<a>(?:(?:ancestor|ancestor-or-self|attribute|child|descendant|descendant-or-self|following|following-sibling|namespace|parent|preceding|preceding-sibling|self)\\s*::|@?)\\s*(?:(?:\\*|(?<l>[-\\w]+)\\s*:\\s*\\*|(?<m>(?:(?&l)\\s*:\\s*(?<s>(?&l)))|(?&s)))|(?<i>comment|text|processing-instruction|node)\\s*\\(\\s*\\)|processing-instruction\\s*\\(\\s*(?<j>"[^"]*"|\'[^\']*\')\\s*\\))\\s*(?:(?<d>(?:\\[\\s*(?:(?<q>(?:(?<u>(?:(?<w>(?<x>(?<y>(?<z>(?:(?<ac>(?:(?:(?:\\$\\s*(?&m))|\\(\\s*(?&q)\\s*\\)|(?&j)|(?:(?<an>[0-9]+)\\s*(?:\\.\\s*(?&an)?)?|\\.\\s*(?&an))|(?:(?!(?&i))(?&m)\\s*\\(\\s*(?:(?<ap>(?&q)\\s*(?:,\\s*(?&ap))?))?\\s*\\)))\\s*(?&d)?)\\s*(?://?\\s*(?<ae>(?&a)|(?:(?:(?&a)\\s*//?)*\\s*(?&a))\\s*/\\s*(?&a)|(?:(?:(?:(?&a)\\s*//?)*\\s*(?&a))\\s*//\\s*(?&a))))?|(?:(?&ae)|(?:/\\s*(?&ae)?|(?://\\s*(?&ae)))))|(?:(?:(?&ac)\\s*\\|)*\\s*(?&ac))\\s*\\|\\s*(?&ac))|-\\s*(?&z))\\s*(?:(?:\\*|div|mod)\\s*(?&z))?)\\s*(?:[-+]\\s*(?&y))?)\\s*(?:[<>]=?\\s*(?&x))?)\\s*(?:!?=\\s*(?&w))?)\\s*(?:and\\s*(?&u))?)\\s*(?:or\\s*(?&u))?)))\\s*\\])\\s*(?&d)?))?|\\.\\.?))\\s*//?)*\\s*(?<Step1>(?&a)))\\s*//\\s*(?<Step2>(?&a)))\\s*$)',
		'AbbreviatedStep' => '(^\\s*\\.\\.?\\s*$)',
		'AbsoluteLocationPath' => '(^\\s*(?:/\\s*(?<RelativeLocationPath0>(?<a>(?<c>(?:(?:ancestor|ancestor-or-self|attribute|child|descendant|descendant-or-self|following|following-sibling|namespace|parent|preceding|preceding-sibling|self)\\s*::|@?)\\s*(?:(?:\\*|(?<o>[-\\w]+)\\s*:\\s*\\*|(?<p>(?:(?&o)\\s*:\\s*(?<v>(?&o)))|(?&v)))|(?<l>comment|text|processing-instruction|node)\\s*\\(\\s*\\)|processing-instruction\\s*\\(\\s*(?<m>"[^"]*"|\'[^\']*\')\\s*\\))\\s*(?:(?<g>(?:\\[\\s*(?:(?<t>(?:(?<x>(?:(?<z>(?<aa>(?<ab>(?<ac>(?:(?<af>(?:(?:(?:\\$\\s*(?&p))|\\(\\s*(?&t)\\s*\\)|(?&m)|(?:(?<an>[0-9]+)\\s*(?:\\.\\s*(?&an)?)?|\\.\\s*(?&an))|(?:(?!(?&l))(?&p)\\s*\\(\\s*(?:(?<ap>(?&t)\\s*(?:,\\s*(?&ap))?))?\\s*\\)))\\s*(?&g)?)\\s*(?://?\\s*(?&a))?|(?:(?&a)|(?:/\\s*(?&a)?|(?&b))))|(?:(?:(?&af)\\s*\\|)*\\s*(?&af))\\s*\\|\\s*(?&af))|-\\s*(?&ac))\\s*(?:(?:\\*|div|mod)\\s*(?&ac))?)\\s*(?:[-+]\\s*(?&ab))?)\\s*(?:[<>]=?\\s*(?&aa))?)\\s*(?:!?=\\s*(?&z))?)\\s*(?:and\\s*(?&x))?)\\s*(?:or\\s*(?&x))?)))\\s*\\])\\s*(?&g)?))?|\\.\\.?)|(?:(?:(?&c)\\s*//?)*\\s*(?&c))\\s*/\\s*(?&c)|(?:(?:(?:(?&c)\\s*//?)*\\s*(?&c))\\s*//\\s*(?&c))))?|(?<AbbreviatedAbsoluteLocationPath0>(?<b>//\\s*(?&a))))\\s*$)',
		'AdditiveExpr' => '(^\\s*(?:(?<MultiplicativeExpr0>(?<a>(?<b>(?:(?<e>(?:(?:(?:\\$\\s*(?<x>(?:(?&al)\\s*:\\s*(?<an>(?&al)))|(?&an)))|\\(\\s*(?<o>(?:(?<aj>(?:(?<ap>(?<aq>(?&a)\\s*(?:[-+]\\s*(?&a))?)\\s*(?:[<>]=?\\s*(?&aq))?)\\s*(?:!?=\\s*(?&ap))?)\\s*(?:and\\s*(?&aj))?)\\s*(?:or\\s*(?&aj))?))\\s*\\)|(?<p>"[^"]*"|\'[^\']*\')|(?:(?<z>[0-9]+)\\s*(?:\\.\\s*(?&z)?)?|\\.\\s*(?&z))|(?:(?!(?&ag))(?&x)\\s*\\(\\s*(?:(?<ab>(?&o)\\s*(?:,\\s*(?&ab))?))?\\s*\\)))\\s*(?:(?<j>(?:\\[\\s*(?&o)\\s*\\])\\s*(?&j)?))?)\\s*(?://?\\s*(?<g>(?<k>(?:(?:ancestor|ancestor-or-self|attribute|child|descendant|descendant-or-self|following|following-sibling|namespace|parent|preceding|preceding-sibling|self)\\s*::|@?)\\s*(?:(?:\\*|(?<al>[-\\w]+)\\s*:\\s*\\*|(?&x))|(?<ag>comment|text|processing-instruction|node)\\s*\\(\\s*\\)|processing-instruction\\s*\\(\\s*(?&p)\\s*\\))\\s*(?&j)?|\\.\\.?)|(?:(?:(?&k)\\s*//?)*\\s*(?&k))\\s*/\\s*(?&k)|(?:(?:(?:(?&k)\\s*//?)*\\s*(?&k))\\s*//\\s*(?&k))))?|(?:(?&g)|(?:/\\s*(?&g)?|(?://\\s*(?&g)))))|(?:(?:(?&e)\\s*\\|)*\\s*(?&e))\\s*\\|\\s*(?&e))|-\\s*(?&b))\\s*(?:(?:\\*|div|mod)\\s*(?&b))?))\\s*(?:[-+]\\s*(?<MultiplicativeExpr1>(?&a)))?)\\s*$)',
		'AndExpr' => '(^\\s*(?:(?<EqualityExpr0>(?<a>(?<c>(?<d>(?<e>(?<f>(?:(?<i>(?:(?:(?:\\$\\s*(?<ab>(?:(?&ao)\\s*:\\s*(?<aq>(?&ao)))|(?&aq)))|\\(\\s*(?<s>(?:(?&b)\\s*(?:or\\s*(?&b))?))\\s*\\)|(?<t>"[^"]*"|\'[^\']*\')|(?:(?<ad>[0-9]+)\\s*(?:\\.\\s*(?&ad)?)?|\\.\\s*(?&ad))|(?:(?!(?&ak))(?&ab)\\s*\\(\\s*(?:(?<af>(?&s)\\s*(?:,\\s*(?&af))?))?\\s*\\)))\\s*(?:(?<n>(?:\\[\\s*(?&s)\\s*\\])\\s*(?&n)?))?)\\s*(?://?\\s*(?<k>(?<o>(?:(?:ancestor|ancestor-or-self|attribute|child|descendant|descendant-or-self|following|following-sibling|namespace|parent|preceding|preceding-sibling|self)\\s*::|@?)\\s*(?:(?:\\*|(?<ao>[-\\w]+)\\s*:\\s*\\*|(?&ab))|(?<ak>comment|text|processing-instruction|node)\\s*\\(\\s*\\)|processing-instruction\\s*\\(\\s*(?&t)\\s*\\))\\s*(?&n)?|\\.\\.?)|(?:(?:(?&o)\\s*//?)*\\s*(?&o))\\s*/\\s*(?&o)|(?:(?:(?:(?&o)\\s*//?)*\\s*(?&o))\\s*//\\s*(?&o))))?|(?:(?&k)|(?:/\\s*(?&k)?|(?://\\s*(?&k)))))|(?:(?:(?&i)\\s*\\|)*\\s*(?&i))\\s*\\|\\s*(?&i))|-\\s*(?&f))\\s*(?:(?:\\*|div|mod)\\s*(?&f))?)\\s*(?:[-+]\\s*(?&e))?)\\s*(?:[<>]=?\\s*(?&d))?)\\s*(?:!?=\\s*(?&c))?))\\s*(?:and\\s*(?<AndExpr0>(?<b>(?&a)\\s*(?:and\\s*(?&b))?)))?)\\s*$)',
		'Argument' => '(^\\s*(?:(?<Expr0>(?<a>(?:(?<c>(?:(?<e>(?<f>(?<g>(?<h>(?:(?<k>(?:(?:(?:\\$\\s*(?<ac>(?:(?&ao)\\s*:\\s*(?<aq>(?&ao)))|(?&aq)))|\\(\\s*(?&a)\\s*\\)|(?<u>"[^"]*"|\'[^\']*\')|(?:(?<ad>[0-9]+)\\s*(?:\\.\\s*(?&ad)?)?|\\.\\s*(?&ad))|(?:(?!(?&ak))(?&ac)\\s*\\(\\s*(?:(?<af>(?&a)\\s*(?:,\\s*(?&af))?))?\\s*\\)))\\s*(?:(?<p>(?:\\[\\s*(?&a)\\s*\\])\\s*(?&p)?))?)\\s*(?://?\\s*(?<m>(?<q>(?:(?:ancestor|ancestor-or-self|attribute|child|descendant|descendant-or-self|following|following-sibling|namespace|parent|preceding|preceding-sibling|self)\\s*::|@?)\\s*(?:(?:\\*|(?<ao>[-\\w]+)\\s*:\\s*\\*|(?&ac))|(?<ak>comment|text|processing-instruction|node)\\s*\\(\\s*\\)|processing-instruction\\s*\\(\\s*(?&u)\\s*\\))\\s*(?&p)?|\\.\\.?)|(?:(?:(?&q)\\s*//?)*\\s*(?&q))\\s*/\\s*(?&q)|(?:(?:(?:(?&q)\\s*//?)*\\s*(?&q))\\s*//\\s*(?&q))))?|(?:(?&m)|(?:/\\s*(?&m)?|(?://\\s*(?&m)))))|(?:(?:(?&k)\\s*\\|)*\\s*(?&k))\\s*\\|\\s*(?&k))|-\\s*(?&h))\\s*(?:(?:\\*|div|mod)\\s*(?&h))?)\\s*(?:[-+]\\s*(?&g))?)\\s*(?:[<>]=?\\s*(?&f))?)\\s*(?:!?=\\s*(?&e))?)\\s*(?:and\\s*(?&c))?)\\s*(?:or\\s*(?&c))?))))\\s*$)',
		'AxisName' => '(^\\s*(?:ancestor|ancestor-or-self|attribute|child|descendant|descendant-or-self|following|following-sibling|namespace|parent|preceding|preceding-sibling|self)\\s*$)',
		'AxisSpecifier' => '(^\\s*(?:(?<AxisName0>(?:ancestor|ancestor-or-self|attribute|child|descendant|descendant-or-self|following|following-sibling|namespace|parent|preceding|preceding-sibling|self))\\s*::|(?<AbbreviatedAxisSpecifier0>@?))\\s*$)',
		'Digits' => '(^\\s*[0-9]+\\s*$)',
		'EqualityExpr' => '(^\\s*(?:(?<RelationalExpr0>(?<a>(?<b>(?<c>(?<d>(?:(?<g>(?:(?:(?:\\$\\s*(?<z>(?:(?&an)\\s*:\\s*(?<ap>(?&an)))|(?&ap)))|\\(\\s*(?<q>(?:(?<al>(?:(?&a)\\s*(?:!?=\\s*(?&a))?)\\s*(?:and\\s*(?&al))?)\\s*(?:or\\s*(?&al))?))\\s*\\)|(?<r>"[^"]*"|\'[^\']*\')|(?:(?<ab>[0-9]+)\\s*(?:\\.\\s*(?&ab)?)?|\\.\\s*(?&ab))|(?:(?!(?&ai))(?&z)\\s*\\(\\s*(?:(?<ad>(?&q)\\s*(?:,\\s*(?&ad))?))?\\s*\\)))\\s*(?:(?<l>(?:\\[\\s*(?&q)\\s*\\])\\s*(?&l)?))?)\\s*(?://?\\s*(?<i>(?<m>(?:(?:ancestor|ancestor-or-self|attribute|child|descendant|descendant-or-self|following|following-sibling|namespace|parent|preceding|preceding-sibling|self)\\s*::|@?)\\s*(?:(?:\\*|(?<an>[-\\w]+)\\s*:\\s*\\*|(?&z))|(?<ai>comment|text|processing-instruction|node)\\s*\\(\\s*\\)|processing-instruction\\s*\\(\\s*(?&r)\\s*\\))\\s*(?&l)?|\\.\\.?)|(?:(?:(?&m)\\s*//?)*\\s*(?&m))\\s*/\\s*(?&m)|(?:(?:(?:(?&m)\\s*//?)*\\s*(?&m))\\s*//\\s*(?&m))))?|(?:(?&i)|(?:/\\s*(?&i)?|(?://\\s*(?&i)))))|(?:(?:(?&g)\\s*\\|)*\\s*(?&g))\\s*\\|\\s*(?&g))|-\\s*(?&d))\\s*(?:(?:\\*|div|mod)\\s*(?&d))?)\\s*(?:[-+]\\s*(?&c))?)\\s*(?:[<>]=?\\s*(?&b))?))\\s*(?:!?=\\s*(?<RelationalExpr1>(?&a)))?)\\s*$)',
		'Expr' => '(^\\s*(?:(?<OrExpr0>(?<a>(?<b>(?:(?<d>(?<e>(?<f>(?<g>(?:(?<j>(?:(?:(?:\\$\\s*(?<ac>(?:(?&ao)\\s*:\\s*(?<aq>(?&ao)))|(?&aq)))|\\(\\s*(?<t>(?&a))\\s*\\)|(?<u>"[^"]*"|\'[^\']*\')|(?:(?<ad>[0-9]+)\\s*(?:\\.\\s*(?&ad)?)?|\\.\\s*(?&ad))|(?:(?!(?&ak))(?&ac)\\s*\\(\\s*(?:(?<af>(?&t)\\s*(?:,\\s*(?&af))?))?\\s*\\)))\\s*(?:(?<o>(?:\\[\\s*(?&t)\\s*\\])\\s*(?&o)?))?)\\s*(?://?\\s*(?<l>(?<p>(?:(?:ancestor|ancestor-or-self|attribute|child|descendant|descendant-or-self|following|following-sibling|namespace|parent|preceding|preceding-sibling|self)\\s*::|@?)\\s*(?:(?:\\*|(?<ao>[-\\w]+)\\s*:\\s*\\*|(?&ac))|(?<ak>comment|text|processing-instruction|node)\\s*\\(\\s*\\)|processing-instruction\\s*\\(\\s*(?&u)\\s*\\))\\s*(?&o)?|\\.\\.?)|(?:(?:(?&p)\\s*//?)*\\s*(?&p))\\s*/\\s*(?&p)|(?:(?:(?:(?&p)\\s*//?)*\\s*(?&p))\\s*//\\s*(?&p))))?|(?:(?&l)|(?:/\\s*(?&l)?|(?://\\s*(?&l)))))|(?:(?:(?&j)\\s*\\|)*\\s*(?&j))\\s*\\|\\s*(?&j))|-\\s*(?&g))\\s*(?:(?:\\*|div|mod)\\s*(?&g))?)\\s*(?:[-+]\\s*(?&f))?)\\s*(?:[<>]=?\\s*(?&e))?)\\s*(?:!?=\\s*(?&d))?)\\s*(?:and\\s*(?&b))?)\\s*(?:or\\s*(?&b))?)))\\s*$)',
		'FilterExpr' => '(^\\s*(?:(?<PrimaryExpr0>(?<a>(?:\\$\\s*(?<i>(?:(?:(?<w>[-\\w]+))\\s*:\\s*(?<u>(?&w)))|(?&u)))|\\(\\s*(?<d>(?:(?<q>(?:(?<x>(?<y>(?<z>(?<aa>(?:(?<ad>(?:(?&a)\\s*(?&b)?)\\s*(?://?\\s*(?<af>(?<ah>(?:(?:ancestor|ancestor-or-self|attribute|child|descendant|descendant-or-self|following|following-sibling|namespace|parent|preceding|preceding-sibling|self)\\s*::|@?)\\s*(?:(?:\\*|(?&w)\\s*:\\s*\\*|(?&i))|(?&r)\\s*\\(\\s*\\)|processing-instruction\\s*\\(\\s*(?&e)\\s*\\))\\s*(?&b)?|\\.\\.?)|(?:(?:(?&ah)\\s*//?)*\\s*(?&ah))\\s*/\\s*(?&ah)|(?:(?:(?:(?&ah)\\s*//?)*\\s*(?&ah))\\s*//\\s*(?&ah))))?|(?:(?&af)|(?:/\\s*(?&af)?|(?://\\s*(?&af)))))|(?:(?:(?&ad)\\s*\\|)*\\s*(?&ad))\\s*\\|\\s*(?&ad))|-\\s*(?&aa))\\s*(?:(?:\\*|div|mod)\\s*(?&aa))?)\\s*(?:[-+]\\s*(?&z))?)\\s*(?:[<>]=?\\s*(?&y))?)\\s*(?:!?=\\s*(?&x))?)\\s*(?:and\\s*(?&q))?)\\s*(?:or\\s*(?&q))?))\\s*\\)|(?<e>"[^"]*"|\'[^\']*\')|(?:(?<k>[0-9]+)\\s*(?:\\.\\s*(?&k)?)?|\\.\\s*(?&k))|(?:(?:(?!(?<r>comment|text|processing-instruction|node))(?&i))\\s*\\(\\s*(?:(?<m>(?&d)\\s*(?:,\\s*(?&m))?))?\\s*\\))))\\s*(?<predicates0>(?<predicates1>(?<b>(?:\\[\\s*(?&d)\\s*\\])\\s*(?&b)?)))?)\\s*$)',
		'FunctionCall' => '(^\\s*(?:(?<FunctionName0>(?<a>(?!(?<c>comment|text|processing-instruction|node))(?<d>(?:(?:(?<l>[-\\w]+))\\s*:\\s*(?<j>(?&l)))|(?&j))))\\s*\\(\\s*(?<arguments0>(?<arguments1>(?<b>(?:(?<h>(?:(?<m>(?:(?<o>(?<p>(?<q>(?<r>(?:(?<u>(?:(?:(?:\\$\\s*(?&d))|\\(\\s*(?&h)\\s*\\)|(?<ae>"[^"]*"|\'[^\']*\')|(?:(?<am>[0-9]+)\\s*(?:\\.\\s*(?&am)?)?|\\.\\s*(?&am))|(?:(?&a)\\s*\\(\\s*(?&b)?\\s*\\)))\\s*(?:(?<z>(?:\\[\\s*(?&h)\\s*\\])\\s*(?&z)?))?)\\s*(?://?\\s*(?<w>(?<aa>(?:(?:ancestor|ancestor-or-self|attribute|child|descendant|descendant-or-self|following|following-sibling|namespace|parent|preceding|preceding-sibling|self)\\s*::|@?)\\s*(?:(?:\\*|(?&l)\\s*:\\s*\\*|(?&d))|(?&c)\\s*\\(\\s*\\)|processing-instruction\\s*\\(\\s*(?&ae)\\s*\\))\\s*(?&z)?|\\.\\.?)|(?:(?:(?&aa)\\s*//?)*\\s*(?&aa))\\s*/\\s*(?&aa)|(?:(?:(?:(?&aa)\\s*//?)*\\s*(?&aa))\\s*//\\s*(?&aa))))?|(?:(?&w)|(?:/\\s*(?&w)?|(?://\\s*(?&w)))))|(?:(?:(?&u)\\s*\\|)*\\s*(?&u))\\s*\\|\\s*(?&u))|-\\s*(?&r))\\s*(?:(?:\\*|div|mod)\\s*(?&r))?)\\s*(?:[-+]\\s*(?&q))?)\\s*(?:[<>]=?\\s*(?&p))?)\\s*(?:!?=\\s*(?&o))?)\\s*(?:and\\s*(?&m))?)\\s*(?:or\\s*(?&m))?)))\\s*(?:,\\s*(?&b))?)))?\\s*\\))\\s*$)',
		'FunctionName' => '(^\\s*(?:(?!(?<NodeType0>(?:comment|text|processing-instruction|node)))(?<QName0>(?:(?:(?:(?<g>[-\\w]+))\\s*:\\s*(?<f>(?&g)))|(?&f))))\\s*$)',
		'Literal' => '(^\\s*(?:"[^"]*"|\'[^\']*\')\\s*$)',
		'LocalPart' => '(^\\s*(?:(?<NCName0>[-\\w]+))\\s*$)',
		'LocationPath' => '(^\\s*(?:(?<RelativeLocationPath0>(?<a>(?<c>(?:(?:ancestor|ancestor-or-self|attribute|child|descendant|descendant-or-self|following|following-sibling|namespace|parent|preceding|preceding-sibling|self)\\s*::|@?)\\s*(?:(?:\\*|(?<p>[-\\w]+)\\s*:\\s*\\*|(?<q>(?:(?&p)\\s*:\\s*(?<w>(?&p)))|(?&w)))|(?<m>comment|text|processing-instruction|node)\\s*\\(\\s*\\)|processing-instruction\\s*\\(\\s*(?<n>"[^"]*"|\'[^\']*\')\\s*\\))\\s*(?:(?<h>(?:\\[\\s*(?:(?<u>(?:(?<y>(?:(?<aa>(?<ab>(?<ac>(?<ad>(?:(?<ag>(?:(?:(?:\\$\\s*(?&q))|\\(\\s*(?&u)\\s*\\)|(?&n)|(?:(?<an>[0-9]+)\\s*(?:\\.\\s*(?&an)?)?|\\.\\s*(?&an))|(?:(?!(?&m))(?&q)\\s*\\(\\s*(?:(?<ap>(?&u)\\s*(?:,\\s*(?&ap))?))?\\s*\\)))\\s*(?&h)?)\\s*(?://?\\s*(?&a))?|(?:(?&a)|(?&b)))|(?:(?:(?&ag)\\s*\\|)*\\s*(?&ag))\\s*\\|\\s*(?&ag))|-\\s*(?&ad))\\s*(?:(?:\\*|div|mod)\\s*(?&ad))?)\\s*(?:[-+]\\s*(?&ac))?)\\s*(?:[<>]=?\\s*(?&ab))?)\\s*(?:!?=\\s*(?&aa))?)\\s*(?:and\\s*(?&y))?)\\s*(?:or\\s*(?&y))?)))\\s*\\])\\s*(?&h)?))?|\\.\\.?)|(?:(?:(?&c)\\s*//?)*\\s*(?&c))\\s*/\\s*(?&c)|(?:(?:(?:(?&c)\\s*//?)*\\s*(?&c))\\s*//\\s*(?&c))))|(?<AbsoluteLocationPath0>(?<b>/\\s*(?&a)?|(?://\\s*(?&a)))))\\s*$)',
		'MultiplicativeExpr' => '(^\\s*(?:(?<UnaryExpr0>(?<a>(?:(?<d>(?:(?:(?:\\$\\s*(?<w>(?:(?&ak)\\s*:\\s*(?<am>(?&ak)))|(?&am)))|\\(\\s*(?<n>(?:(?<ai>(?:(?<ao>(?<ap>(?<aq>(?&a)\\s*(?:(?:(?&b)|div|mod)\\s*(?&a))?)\\s*(?:[-+]\\s*(?&aq))?)\\s*(?:[<>]=?\\s*(?&ap))?)\\s*(?:!?=\\s*(?&ao))?)\\s*(?:and\\s*(?&ai))?)\\s*(?:or\\s*(?&ai))?))\\s*\\)|(?<o>"[^"]*"|\'[^\']*\')|(?:(?<y>[0-9]+)\\s*(?:\\.\\s*(?&y)?)?|\\.\\s*(?&y))|(?:(?!(?&af))(?&w)\\s*\\(\\s*(?:(?<aa>(?&n)\\s*(?:,\\s*(?&aa))?))?\\s*\\)))\\s*(?:(?<i>(?:\\[\\s*(?&n)\\s*\\])\\s*(?&i)?))?)\\s*(?://?\\s*(?<f>(?<j>(?:(?:ancestor|ancestor-or-self|attribute|child|descendant|descendant-or-self|following|following-sibling|namespace|parent|preceding|preceding-sibling|self)\\s*::|@?)\\s*(?:(?:\\*|(?<ak>[-\\w]+)\\s*:\\s*\\*|(?&w))|(?<af>comment|text|processing-instruction|node)\\s*\\(\\s*\\)|processing-instruction\\s*\\(\\s*(?&o)\\s*\\))\\s*(?&i)?|\\.\\.?)|(?:(?:(?&j)\\s*//?)*\\s*(?&j))\\s*/\\s*(?&j)|(?:(?:(?:(?&j)\\s*//?)*\\s*(?&j))\\s*//\\s*(?&j))))?|(?:(?&f)|(?:/\\s*(?&f)?|(?://\\s*(?&f)))))|(?:(?:(?&d)\\s*\\|)*\\s*(?&d))\\s*\\|\\s*(?&d))|-\\s*(?&a)))\\s*(?:(?:(?<MultiplyOperator0>(?<b>\\*))|div|mod)\\s*(?<UnaryExpr1>(?&a)))?)\\s*$)',
		'MultiplyOperator' => '(^\\s*\\*\\s*$)',
		'NCName' => '(^\\s*[-\\w]+\\s*$)',
		'NameTest' => '(^\\s*(?:\\*|(?<NCName0>(?<a>[-\\w]+))\\s*:\\s*\\*|(?<QName0>(?:(?:(?&a)\\s*:\\s*(?<f>(?&a)))|(?&f))))\\s*$)',
		'NodeTest' => '(^\\s*(?:(?<NameTest0>(?:\\*|(?<d>[-\\w]+)\\s*:\\s*\\*|(?:(?:(?&d)\\s*:\\s*(?<i>(?&d)))|(?&i))))|(?<NodeType0>(?:comment|text|processing-instruction|node))\\s*\\(\\s*\\)|processing-instruction\\s*\\(\\s*(?<Literal0>(?:"[^"]*"|\'[^\']*\'))\\s*\\))\\s*$)',
		'NodeType' => '(^\\s*(?:comment|text|processing-instruction|node)\\s*$)',
		'Number' => '(^\\s*(?:(?<Digits0>(?<a>[0-9]+))\\s*(?:\\.\\s*(?<Digits1>(?&a))?)?|\\.\\s*(?<Digits2>(?&a)))\\s*$)',
		'Operator' => '(^\\s*(?:(?<OperatorName0>(?:and|or|mod|div))|(?<MultiplyOperator0>\\*)|//?|\\||\\+|-|=|!=|<=?|>=?)\\s*$)',
		'OperatorName' => '(^\\s*(?:and|or|mod|div)\\s*$)',
		'OrExpr' => '(^\\s*(?:(?<AndExpr0>(?<a>(?:(?<c>(?<d>(?<e>(?<f>(?:(?<i>(?:(?:(?:\\$\\s*(?<ab>(?:(?&ao)\\s*:\\s*(?<aq>(?&ao)))|(?&aq)))|\\(\\s*(?<s>(?:(?&a)\\s*(?:or\\s*(?&a))?))\\s*\\)|(?<t>"[^"]*"|\'[^\']*\')|(?:(?<ad>[0-9]+)\\s*(?:\\.\\s*(?&ad)?)?|\\.\\s*(?&ad))|(?:(?!(?&ak))(?&ab)\\s*\\(\\s*(?:(?<af>(?&s)\\s*(?:,\\s*(?&af))?))?\\s*\\)))\\s*(?:(?<n>(?:\\[\\s*(?&s)\\s*\\])\\s*(?&n)?))?)\\s*(?://?\\s*(?<k>(?<o>(?:(?:ancestor|ancestor-or-self|attribute|child|descendant|descendant-or-self|following|following-sibling|namespace|parent|preceding|preceding-sibling|self)\\s*::|@?)\\s*(?:(?:\\*|(?<ao>[-\\w]+)\\s*:\\s*\\*|(?&ab))|(?<ak>comment|text|processing-instruction|node)\\s*\\(\\s*\\)|processing-instruction\\s*\\(\\s*(?&t)\\s*\\))\\s*(?&n)?|\\.\\.?)|(?:(?:(?&o)\\s*//?)*\\s*(?&o))\\s*/\\s*(?&o)|(?:(?:(?:(?&o)\\s*//?)*\\s*(?&o))\\s*//\\s*(?&o))))?|(?:(?&k)|(?:/\\s*(?&k)?|(?://\\s*(?&k)))))|(?:(?:(?&i)\\s*\\|)*\\s*(?&i))\\s*\\|\\s*(?&i))|-\\s*(?&f))\\s*(?:(?:\\*|div|mod)\\s*(?&f))?)\\s*(?:[-+]\\s*(?&e))?)\\s*(?:[<>]=?\\s*(?&d))?)\\s*(?:!?=\\s*(?&c))?)\\s*(?:and\\s*(?&a))?))\\s*(?:or\\s*(?<AndExpr1>(?&a)))?)\\s*$)',
		'PathExpr' => '(^\\s*(?:(?<FilterExpr0>(?<a>(?:(?:\\$\\s*(?<s>(?:(?&ag)\\s*:\\s*(?<ai>(?&ag)))|(?&ai)))|\\(\\s*(?<j>(?:(?<ae>(?:(?<ak>(?<al>(?<am>(?<an>(?:(?<aq>(?&a)\\s*(?://?\\s*(?&b))?|(?&c))|(?:(?:(?&aq)\\s*\\|)*\\s*(?&aq))\\s*\\|\\s*(?&aq))|-\\s*(?&an))\\s*(?:(?:\\*|div|mod)\\s*(?&an))?)\\s*(?:[-+]\\s*(?&am))?)\\s*(?:[<>]=?\\s*(?&al))?)\\s*(?:!?=\\s*(?&ak))?)\\s*(?:and\\s*(?&ae))?)\\s*(?:or\\s*(?&ae))?))\\s*\\)|(?<k>"[^"]*"|\'[^\']*\')|(?:(?<u>[0-9]+)\\s*(?:\\.\\s*(?&u)?)?|\\.\\s*(?&u))|(?:(?!(?&ab))(?&s)\\s*\\(\\s*(?:(?<w>(?&j)\\s*(?:,\\s*(?&w))?))?\\s*\\)))\\s*(?:(?<e>(?:\\[\\s*(?&j)\\s*\\])\\s*(?&e)?))?))\\s*(?://?\\s*(?<RelativeLocationPath0>(?<b>(?<f>(?:(?:ancestor|ancestor-or-self|attribute|child|descendant|descendant-or-self|following|following-sibling|namespace|parent|preceding|preceding-sibling|self)\\s*::|@?)\\s*(?:(?:\\*|(?<ag>[-\\w]+)\\s*:\\s*\\*|(?&s))|(?<ab>comment|text|processing-instruction|node)\\s*\\(\\s*\\)|processing-instruction\\s*\\(\\s*(?&k)\\s*\\))\\s*(?&e)?|\\.\\.?)|(?:(?:(?&f)\\s*//?)*\\s*(?&f))\\s*/\\s*(?&f)|(?:(?:(?:(?&f)\\s*//?)*\\s*(?&f))\\s*//\\s*(?&f)))))?|(?<LocationPath0>(?<c>(?&b)|(?:/\\s*(?&b)?|(?://\\s*(?&b))))))\\s*$)',
		'Predicate' => '(^\\s*(?:\\[\\s*(?<PredicateExpr0>(?<a>(?<b>(?:(?<d>(?:(?<f>(?<g>(?<h>(?<i>(?:(?<l>(?:(?:(?:\\$\\s*(?<ad>(?:(?&ao)\\s*:\\s*(?<aq>(?&ao)))|(?&aq)))|\\(\\s*(?&b)\\s*\\)|(?<v>"[^"]*"|\'[^\']*\')|(?:(?<ae>[0-9]+)\\s*(?:\\.\\s*(?&ae)?)?|\\.\\s*(?&ae))|(?:(?!(?&ak))(?&ad)\\s*\\(\\s*(?:(?<ag>(?&b)\\s*(?:,\\s*(?&ag))?))?\\s*\\)))\\s*(?:(?<q>(?:\\[\\s*(?&a)\\s*\\])\\s*(?&q)?))?)\\s*(?://?\\s*(?<n>(?<r>(?:(?:ancestor|ancestor-or-self|attribute|child|descendant|descendant-or-self|following|following-sibling|namespace|parent|preceding|preceding-sibling|self)\\s*::|@?)\\s*(?:(?:\\*|(?<ao>[-\\w]+)\\s*:\\s*\\*|(?&ad))|(?<ak>comment|text|processing-instruction|node)\\s*\\(\\s*\\)|processing-instruction\\s*\\(\\s*(?&v)\\s*\\))\\s*(?&q)?|\\.\\.?)|(?:(?:(?&r)\\s*//?)*\\s*(?&r))\\s*/\\s*(?&r)|(?:(?:(?:(?&r)\\s*//?)*\\s*(?&r))\\s*//\\s*(?&r))))?|(?:(?&n)|(?:/\\s*(?&n)?|(?://\\s*(?&n)))))|(?:(?:(?&l)\\s*\\|)*\\s*(?&l))\\s*\\|\\s*(?&l))|-\\s*(?&i))\\s*(?:(?:\\*|div|mod)\\s*(?&i))?)\\s*(?:[-+]\\s*(?&h))?)\\s*(?:[<>]=?\\s*(?&g))?)\\s*(?:!?=\\s*(?&f))?)\\s*(?:and\\s*(?&d))?)\\s*(?:or\\s*(?&d))?))))\\s*\\])\\s*$)',
		'PredicateExpr' => '(^\\s*(?:(?<Expr0>(?<a>(?:(?<c>(?:(?<e>(?<f>(?<g>(?<h>(?:(?<k>(?:(?:(?:\\$\\s*(?<ac>(?:(?&ao)\\s*:\\s*(?<aq>(?&ao)))|(?&aq)))|\\(\\s*(?&a)\\s*\\)|(?<u>"[^"]*"|\'[^\']*\')|(?:(?<ad>[0-9]+)\\s*(?:\\.\\s*(?&ad)?)?|\\.\\s*(?&ad))|(?:(?!(?&ak))(?&ac)\\s*\\(\\s*(?:(?<af>(?&a)\\s*(?:,\\s*(?&af))?))?\\s*\\)))\\s*(?:(?<p>(?:\\[\\s*(?&a)\\s*\\])\\s*(?&p)?))?)\\s*(?://?\\s*(?<m>(?<q>(?:(?:ancestor|ancestor-or-self|attribute|child|descendant|descendant-or-self|following|following-sibling|namespace|parent|preceding|preceding-sibling|self)\\s*::|@?)\\s*(?:(?:\\*|(?<ao>[-\\w]+)\\s*:\\s*\\*|(?&ac))|(?<ak>comment|text|processing-instruction|node)\\s*\\(\\s*\\)|processing-instruction\\s*\\(\\s*(?&u)\\s*\\))\\s*(?&p)?|\\.\\.?)|(?:(?:(?&q)\\s*//?)*\\s*(?&q))\\s*/\\s*(?&q)|(?:(?:(?:(?&q)\\s*//?)*\\s*(?&q))\\s*//\\s*(?&q))))?|(?:(?&m)|(?:/\\s*(?&m)?|(?://\\s*(?&m)))))|(?:(?:(?&k)\\s*\\|)*\\s*(?&k))\\s*\\|\\s*(?&k))|-\\s*(?&h))\\s*(?:(?:\\*|div|mod)\\s*(?&h))?)\\s*(?:[-+]\\s*(?&g))?)\\s*(?:[<>]=?\\s*(?&f))?)\\s*(?:!?=\\s*(?&e))?)\\s*(?:and\\s*(?&c))?)\\s*(?:or\\s*(?&c))?))))\\s*$)',
		'Prefix' => '(^\\s*(?:(?<NCName0>[-\\w]+))\\s*$)',
		'PrefixedName' => '(^\\s*(?:(?<Prefix0>(?:(?<c>[-\\w]+)))\\s*:\\s*(?<LocalPart0>(?&c)))\\s*$)',
		'PrimaryExpr' => '(^\\s*(?:(?<VariableReference0>(?<a>\\$\\s*(?<f>(?:(?:(?<s>[-\\w]+))\\s*:\\s*(?<q>(?&s)))|(?&q))))|\\(\\s*(?<Expr0>(?<b>(?:(?<m>(?:(?<t>(?<u>(?<v>(?<w>(?:(?<z>(?:(?:(?&a)|\\(\\s*(?&b)\\s*\\)|(?&c)|(?&d)|(?&e))\\s*(?:(?<ae>(?:\\[\\s*(?&b)\\s*\\])\\s*(?&ae)?))?)\\s*(?://?\\s*(?<ab>(?<af>(?:(?:ancestor|ancestor-or-self|attribute|child|descendant|descendant-or-self|following|following-sibling|namespace|parent|preceding|preceding-sibling|self)\\s*::|@?)\\s*(?:(?:\\*|(?&s)\\s*:\\s*\\*|(?&f))|(?&n)\\s*\\(\\s*\\)|processing-instruction\\s*\\(\\s*(?&c)\\s*\\))\\s*(?&ae)?|\\.\\.?)|(?:(?:(?&af)\\s*//?)*\\s*(?&af))\\s*/\\s*(?&af)|(?:(?:(?:(?&af)\\s*//?)*\\s*(?&af))\\s*//\\s*(?&af))))?|(?:(?&ab)|(?:/\\s*(?&ab)?|(?://\\s*(?&ab)))))|(?:(?:(?&z)\\s*\\|)*\\s*(?&z))\\s*\\|\\s*(?&z))|-\\s*(?&w))\\s*(?:(?:\\*|div|mod)\\s*(?&w))?)\\s*(?:[-+]\\s*(?&v))?)\\s*(?:[<>]=?\\s*(?&u))?)\\s*(?:!?=\\s*(?&t))?)\\s*(?:and\\s*(?&m))?)\\s*(?:or\\s*(?&m))?)))\\s*\\)|(?<Literal0>(?<c>"[^"]*"|\'[^\']*\'))|(?<Number0>(?<d>(?<h>[0-9]+)\\s*(?:\\.\\s*(?&h)?)?|\\.\\s*(?&h)))|(?<FunctionCall0>(?<e>(?:(?!(?<n>comment|text|processing-instruction|node))(?&f))\\s*\\(\\s*(?:(?<j>(?&b)\\s*(?:,\\s*(?&j))?))?\\s*\\))))\\s*$)',
		'QName' => '(^\\s*(?:(?<PrefixedName0>(?:(?:(?<e>[-\\w]+))\\s*:\\s*(?<d>(?&e))))|(?<UnprefixedName0>(?&d)))\\s*$)',
		'RelationalExpr' => '(^\\s*(?:(?<AdditiveExpr0>(?<a>(?<b>(?<c>(?:(?<f>(?:(?:(?:\\$\\s*(?<y>(?:(?&am)\\s*:\\s*(?<ao>(?&am)))|(?&ao)))|\\(\\s*(?<p>(?:(?<ak>(?:(?<aq>(?&a)\\s*(?:[<>]=?\\s*(?&a))?)\\s*(?:!?=\\s*(?&aq))?)\\s*(?:and\\s*(?&ak))?)\\s*(?:or\\s*(?&ak))?))\\s*\\)|(?<q>"[^"]*"|\'[^\']*\')|(?:(?<aa>[0-9]+)\\s*(?:\\.\\s*(?&aa)?)?|\\.\\s*(?&aa))|(?:(?!(?&ah))(?&y)\\s*\\(\\s*(?:(?<ac>(?&p)\\s*(?:,\\s*(?&ac))?))?\\s*\\)))\\s*(?:(?<k>(?:\\[\\s*(?&p)\\s*\\])\\s*(?&k)?))?)\\s*(?://?\\s*(?<h>(?<l>(?:(?:ancestor|ancestor-or-self|attribute|child|descendant|descendant-or-self|following|following-sibling|namespace|parent|preceding|preceding-sibling|self)\\s*::|@?)\\s*(?:(?:\\*|(?<am>[-\\w]+)\\s*:\\s*\\*|(?&y))|(?<ah>comment|text|processing-instruction|node)\\s*\\(\\s*\\)|processing-instruction\\s*\\(\\s*(?&q)\\s*\\))\\s*(?&k)?|\\.\\.?)|(?:(?:(?&l)\\s*//?)*\\s*(?&l))\\s*/\\s*(?&l)|(?:(?:(?:(?&l)\\s*//?)*\\s*(?&l))\\s*//\\s*(?&l))))?|(?:(?&h)|(?:/\\s*(?&h)?|(?://\\s*(?&h)))))|(?:(?:(?&f)\\s*\\|)*\\s*(?&f))\\s*\\|\\s*(?&f))|-\\s*(?&c))\\s*(?:(?:\\*|div|mod)\\s*(?&c))?)\\s*(?:[-+]\\s*(?&b))?))\\s*(?:[<>]=?\\s*(?<AdditiveExpr1>(?&a)))?)\\s*$)',
		'RelativeLocationPath' => '(^\\s*(?:(?<Step0>(?<a>(?:(?:ancestor|ancestor-or-self|attribute|child|descendant|descendant-or-self|following|following-sibling|namespace|parent|preceding|preceding-sibling|self)\\s*::|@?)\\s*(?:(?:\\*|(?<m>[-\\w]+)\\s*:\\s*\\*|(?<n>(?:(?&m)\\s*:\\s*(?<t>(?&m)))|(?&t)))|(?<j>comment|text|processing-instruction|node)\\s*\\(\\s*\\)|processing-instruction\\s*\\(\\s*(?<k>"[^"]*"|\'[^\']*\')\\s*\\))\\s*(?:(?<e>(?:\\[\\s*(?:(?<r>(?:(?<v>(?:(?<x>(?<y>(?<z>(?<aa>(?:(?<ad>(?:(?:(?:\\$\\s*(?&n))|\\(\\s*(?&r)\\s*\\)|(?&k)|(?:(?<an>[0-9]+)\\s*(?:\\.\\s*(?&an)?)?|\\.\\s*(?&an))|(?:(?!(?&j))(?&n)\\s*\\(\\s*(?:(?<ap>(?&r)\\s*(?:,\\s*(?&ap))?))?\\s*\\)))\\s*(?&e)?)\\s*(?://?\\s*(?<af>(?&a)|(?:(?:(?&a)\\s*//?)*\\s*(?&a))\\s*/\\s*(?&a)|(?&b)))?|(?:(?&af)|(?:/\\s*(?&af)?|(?://\\s*(?&af)))))|(?:(?:(?&ad)\\s*\\|)*\\s*(?&ad))\\s*\\|\\s*(?&ad))|-\\s*(?&aa))\\s*(?:(?:\\*|div|mod)\\s*(?&aa))?)\\s*(?:[-+]\\s*(?&z))?)\\s*(?:[<>]=?\\s*(?&y))?)\\s*(?:!?=\\s*(?&x))?)\\s*(?:and\\s*(?&v))?)\\s*(?:or\\s*(?&v))?)))\\s*\\])\\s*(?&e)?))?|\\.\\.?))|(?<RelativeLocationPath0>(?:(?<Step1>(?&a))\\s*//?)*\\s*(?<Step2>(?&a)))\\s*/\\s*(?<Step3>(?&a))|(?<AbbreviatedRelativeLocationPath0>(?<b>(?:(?:(?&a)\\s*//?)*\\s*(?&a))\\s*//\\s*(?&a))))\\s*$)',
		'Step' => '(^\\s*(?:(?<AxisSpecifier0>(?<a>(?:ancestor|ancestor-or-self|attribute|child|descendant|descendant-or-self|following|following-sibling|namespace|parent|preceding|preceding-sibling|self)\\s*::|@?))\\s*(?<NodeTest0>(?<b>(?:\\*|(?<k>[-\\w]+)\\s*:\\s*\\*|(?<l>(?:(?&k)\\s*:\\s*(?<r>(?&k)))|(?&r)))|(?<h>comment|text|processing-instruction|node)\\s*\\(\\s*\\)|processing-instruction\\s*\\(\\s*(?<i>"[^"]*"|\'[^\']*\')\\s*\\)))\\s*(?<predicates0>(?<predicates1>(?<c>(?:\\[\\s*(?:(?<p>(?:(?<t>(?:(?<v>(?<w>(?<x>(?<y>(?:(?<ab>(?:(?:(?:\\$\\s*(?&l))|\\(\\s*(?&p)\\s*\\)|(?&i)|(?:(?<an>[0-9]+)\\s*(?:\\.\\s*(?&an)?)?|\\.\\s*(?&an))|(?:(?!(?&h))(?&l)\\s*\\(\\s*(?:(?<ap>(?&p)\\s*(?:,\\s*(?&ap))?))?\\s*\\)))\\s*(?&c)?)\\s*(?://?\\s*(?<ad>(?<ag>(?&a)\\s*(?&b)\\s*(?&c)?|(?&d))|(?:(?:(?&ag)\\s*//?)*\\s*(?&ag))\\s*/\\s*(?&ag)|(?:(?:(?:(?&ag)\\s*//?)*\\s*(?&ag))\\s*//\\s*(?&ag))))?|(?:(?&ad)|(?:/\\s*(?&ad)?|(?://\\s*(?&ad)))))|(?:(?:(?&ab)\\s*\\|)*\\s*(?&ab))\\s*\\|\\s*(?&ab))|-\\s*(?&y))\\s*(?:(?:\\*|div|mod)\\s*(?&y))?)\\s*(?:[-+]\\s*(?&x))?)\\s*(?:[<>]=?\\s*(?&w))?)\\s*(?:!?=\\s*(?&v))?)\\s*(?:and\\s*(?&t))?)\\s*(?:or\\s*(?&t))?)))\\s*\\])\\s*(?&c)?)))?|(?<AbbreviatedStep0>(?<d>\\.\\.?)))\\s*$)',
		'UnaryExpr' => '(^\\s*(?:(?<UnionExpr0>(?<a>(?<c>(?:(?:(?:\\$\\s*(?<v>(?:(?&aj)\\s*:\\s*(?<al>(?&aj)))|(?&al)))|\\(\\s*(?<m>(?:(?<ah>(?:(?<an>(?<ao>(?<ap>(?&b)\\s*(?:(?:\\*|div|mod)\\s*(?&b))?)\\s*(?:[-+]\\s*(?&ap))?)\\s*(?:[<>]=?\\s*(?&ao))?)\\s*(?:!?=\\s*(?&an))?)\\s*(?:and\\s*(?&ah))?)\\s*(?:or\\s*(?&ah))?))\\s*\\)|(?<n>"[^"]*"|\'[^\']*\')|(?:(?<x>[0-9]+)\\s*(?:\\.\\s*(?&x)?)?|\\.\\s*(?&x))|(?:(?!(?&ae))(?&v)\\s*\\(\\s*(?:(?<z>(?&m)\\s*(?:,\\s*(?&z))?))?\\s*\\)))\\s*(?:(?<h>(?:\\[\\s*(?&m)\\s*\\])\\s*(?&h)?))?)\\s*(?://?\\s*(?<e>(?<i>(?:(?:ancestor|ancestor-or-self|attribute|child|descendant|descendant-or-self|following|following-sibling|namespace|parent|preceding|preceding-sibling|self)\\s*::|@?)\\s*(?:(?:\\*|(?<aj>[-\\w]+)\\s*:\\s*\\*|(?&v))|(?<ae>comment|text|processing-instruction|node)\\s*\\(\\s*\\)|processing-instruction\\s*\\(\\s*(?&n)\\s*\\))\\s*(?&h)?|\\.\\.?)|(?:(?:(?&i)\\s*//?)*\\s*(?&i))\\s*/\\s*(?&i)|(?:(?:(?:(?&i)\\s*//?)*\\s*(?&i))\\s*//\\s*(?&i))))?|(?:(?&e)|(?:/\\s*(?&e)?|(?://\\s*(?&e)))))|(?:(?:(?&c)\\s*\\|)*\\s*(?&c))\\s*\\|\\s*(?&c)))|-\\s*(?<UnaryExpr0>(?<b>(?&a)|-\\s*(?&b))))\\s*$)',
		'UnionExpr' => '(^\\s*(?:(?<PathExpr0>(?<a>(?:(?:(?:\\$\\s*(?<t>(?:(?&ah)\\s*:\\s*(?<aj>(?&ah)))|(?&aj)))|\\(\\s*(?<k>(?:(?<af>(?:(?<al>(?<am>(?<an>(?<ao>(?:(?&a)|(?:(?:(?&a)\\s*\\|)*\\s*(?&a))\\s*\\|\\s*(?&a))|-\\s*(?&ao))\\s*(?:(?:\\*|div|mod)\\s*(?&ao))?)\\s*(?:[-+]\\s*(?&an))?)\\s*(?:[<>]=?\\s*(?&am))?)\\s*(?:!?=\\s*(?&al))?)\\s*(?:and\\s*(?&af))?)\\s*(?:or\\s*(?&af))?))\\s*\\)|(?<l>"[^"]*"|\'[^\']*\')|(?:(?<v>[0-9]+)\\s*(?:\\.\\s*(?&v)?)?|\\.\\s*(?&v))|(?:(?!(?&ac))(?&t)\\s*\\(\\s*(?:(?<x>(?&k)\\s*(?:,\\s*(?&x))?))?\\s*\\)))\\s*(?:(?<f>(?:\\[\\s*(?&k)\\s*\\])\\s*(?&f)?))?)\\s*(?://?\\s*(?<c>(?<g>(?:(?:ancestor|ancestor-or-self|attribute|child|descendant|descendant-or-self|following|following-sibling|namespace|parent|preceding|preceding-sibling|self)\\s*::|@?)\\s*(?:(?:\\*|(?<ah>[-\\w]+)\\s*:\\s*\\*|(?&t))|(?<ac>comment|text|processing-instruction|node)\\s*\\(\\s*\\)|processing-instruction\\s*\\(\\s*(?&l)\\s*\\))\\s*(?&f)?|\\.\\.?)|(?:(?:(?&g)\\s*//?)*\\s*(?&g))\\s*/\\s*(?&g)|(?:(?:(?:(?&g)\\s*//?)*\\s*(?&g))\\s*//\\s*(?&g))))?|(?:(?&c)|(?:/\\s*(?&c)?|(?://\\s*(?&c))))))|(?<UnionExpr0>(?:(?<PathExpr1>(?&a))\\s*\\|)*\\s*(?<PathExpr2>(?&a)))\\s*\\|\\s*(?<PathExpr3>(?&a)))\\s*$)',
		'UnprefixedName' => '(^\\s*(?:(?<LocalPart0>[-\\w]+))\\s*$)',
		'VariableReference' => '(^\\s*(?:\\$\\s*(?<QName0>(?:(?:(?:(?<f>[-\\w]+))\\s*:\\s*(?<e>(?&f)))|(?&e))))\\s*$)',
		'arguments' => '(^\\s*(?:(?<Argument0>(?<a>(?<c>(?:(?<e>(?:(?<g>(?<h>(?<i>(?<j>(?:(?<m>(?:(?:(?:\\$\\s*(?<ae>(?:(?&ao)\\s*:\\s*(?<aq>(?&ao)))|(?&aq)))|\\(\\s*(?&c)\\s*\\)|(?<w>"[^"]*"|\'[^\']*\')|(?:(?<af>[0-9]+)\\s*(?:\\.\\s*(?&af)?)?|\\.\\s*(?&af))|(?:(?!(?&al))(?&ae)\\s*\\(\\s*(?&b)?\\s*\\)))\\s*(?:(?<r>(?:\\[\\s*(?&c)\\s*\\])\\s*(?&r)?))?)\\s*(?://?\\s*(?<o>(?<s>(?:(?:ancestor|ancestor-or-self|attribute|child|descendant|descendant-or-self|following|following-sibling|namespace|parent|preceding|preceding-sibling|self)\\s*::|@?)\\s*(?:(?:\\*|(?<ao>[-\\w]+)\\s*:\\s*\\*|(?&ae))|(?<al>comment|text|processing-instruction|node)\\s*\\(\\s*\\)|processing-instruction\\s*\\(\\s*(?&w)\\s*\\))\\s*(?&r)?|\\.\\.?)|(?:(?:(?&s)\\s*//?)*\\s*(?&s))\\s*/\\s*(?&s)|(?:(?:(?:(?&s)\\s*//?)*\\s*(?&s))\\s*//\\s*(?&s))))?|(?:(?&o)|(?:/\\s*(?&o)?|(?://\\s*(?&o)))))|(?:(?:(?&m)\\s*\\|)*\\s*(?&m))\\s*\\|\\s*(?&m))|-\\s*(?&j))\\s*(?:(?:\\*|div|mod)\\s*(?&j))?)\\s*(?:[-+]\\s*(?&i))?)\\s*(?:[<>]=?\\s*(?&h))?)\\s*(?:!?=\\s*(?&g))?)\\s*(?:and\\s*(?&e))?)\\s*(?:or\\s*(?&e))?))))\\s*(?:,\\s*(?<arguments0>(?<arguments1>(?<b>(?&a)\\s*(?:,\\s*(?&b))?))))?)\\s*$)',
		'predicates' => '(^\\s*(?:(?<Predicate0>(?<a>\\[\\s*(?:(?<d>(?:(?<f>(?:(?<h>(?<i>(?<j>(?<k>(?:(?<n>(?:(?:(?:\\$\\s*(?<ad>(?:(?&ao)\\s*:\\s*(?<aq>(?&ao)))|(?&aq)))|\\(\\s*(?&d)\\s*\\)|(?<w>"[^"]*"|\'[^\']*\')|(?:(?<ae>[0-9]+)\\s*(?:\\.\\s*(?&ae)?)?|\\.\\s*(?&ae))|(?:(?!(?&ak))(?&ad)\\s*\\(\\s*(?:(?<ag>(?&d)\\s*(?:,\\s*(?&ag))?))?\\s*\\)))\\s*(?&b)?)\\s*(?://?\\s*(?<p>(?<s>(?:(?:ancestor|ancestor-or-self|attribute|child|descendant|descendant-or-self|following|following-sibling|namespace|parent|preceding|preceding-sibling|self)\\s*::|@?)\\s*(?:(?:\\*|(?<ao>[-\\w]+)\\s*:\\s*\\*|(?&ad))|(?<ak>comment|text|processing-instruction|node)\\s*\\(\\s*\\)|processing-instruction\\s*\\(\\s*(?&w)\\s*\\))\\s*(?&b)?|\\.\\.?)|(?:(?:(?&s)\\s*//?)*\\s*(?&s))\\s*/\\s*(?&s)|(?:(?:(?:(?&s)\\s*//?)*\\s*(?&s))\\s*//\\s*(?&s))))?|(?:(?&p)|(?:/\\s*(?&p)?|(?://\\s*(?&p)))))|(?:(?:(?&n)\\s*\\|)*\\s*(?&n))\\s*\\|\\s*(?&n))|-\\s*(?&k))\\s*(?:(?:\\*|div|mod)\\s*(?&k))?)\\s*(?:[-+]\\s*(?&j))?)\\s*(?:[<>]=?\\s*(?&i))?)\\s*(?:!?=\\s*(?&h))?)\\s*(?:and\\s*(?&f))?)\\s*(?:or\\s*(?&f))?)))\\s*\\]))\\s*(?<predicates0>(?<predicates1>(?<b>(?&a)\\s*(?&b)?)))?)\\s*$)'
	];
}