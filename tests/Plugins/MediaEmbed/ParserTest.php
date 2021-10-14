<?php

namespace s9e\TextFormatter\Tests\Plugins\MediaEmbed;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Parser\Tag;
use s9e\TextFormatter\Plugins\MediaEmbed\Parser;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsJavaScriptRunner;
use s9e\TextFormatter\Tests\Plugins\RenderingTestsRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\MediaEmbed\Parser
*/
class ParserTest extends Test
{
	use ParsingTestsRunner;
	use ParsingTestsJavaScriptRunner;
	use RenderingTestsRunner;

	protected static function populateCache($entries)
	{
		$cacheDir = __DIR__ . '/../../.cache';
		if (!file_exists($cacheDir))
		{
			$cacheDir = sys_get_temp_dir();
		}

		$prefix = $suffix = '';
		if (extension_loaded('zlib'))
		{
			$prefix  = 'compress.zlib://';
			$suffix  = '.gz';
		}

		foreach ($entries as $url => $content)
		{
			$vars     = [$url, ['headers' => [], 'returnHeaders' => true]];
			$cacheKey = strtr(base64_encode(sha1(serialize($vars), true)), '/', '_');

			file_put_contents($prefix . $cacheDir . '/http.' . $cacheKey . $suffix, $content);
		}

		return $cacheDir;
	}

	/**
	* Run a test that involves scraping without causing a fail if the remote server errors out
	*/
	protected function runScrapingTest($methodName, $args)
	{
		try
		{
			call_user_func_array([$this, $methodName], $args);
		}
		catch (\PHPUnit_Framework_Error_Warning $e)
		{
			$msg = $e->getMessage();

			if (strpos($msg, 'HTTP request failed')  !== false
			 || strpos($msg, 'Connection timed out') !== false
			 || strpos($msg, 'Connection refused')   !== false)
			{
				$this->markTestSkipped($msg);
			}

			throw $e;
		}
	}

	/**
	* @testdox The MEDIA tag can be effectively disabled
	*/
	public function testDisableTag()
	{
		$this->configurator->MediaEmbed->add(
			'foo',
			[
				'host'    => 'localhost',
				'extract' => "!localhost/video/(?'id'\\d+)!",
				'iframe'  => ['src' => '//localhost/embed/{@id}']
			]
		);

		$text = 'http://localhost/video/123';
		$parser = $this->getParser();
		$this->assertSame(
			'<r><FOO id="123">http://localhost/video/123</FOO></r>',
			$parser->parse($text)
		);
		$parser->disableTag('MEDIA');
		$this->assertSame(
			'<t>http://localhost/video/123</t>',
			$parser->parse($text)
		);
	}

	/**
	* @testdox Abstract tests (not tied to bundled sites)
	* @dataProvider getAbstractTests
	*/
	public function testAbstract()
	{
		call_user_func_array([$this, 'testParsing'], func_get_args());
	}

	public function getAbstractTests()
	{
		return [
			[
				// Multiple "match" in scrape
				'http://example.invalid/123',
				'<r><EXAMPLE id="456">http://example.invalid/123</EXAMPLE></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = self::populateCache([
						'http://example.invalid/123' => '456'
					]);

					$configurator->MediaEmbed->add(
						'example',
						[
							'host'   => 'example.invalid',
							'scrape' => [
								'match'   => ['/XXX/', '/123/'],
								'extract' => "!^(?'id'[0-9]+)$!"
							],
							'iframe' => [
								'width'  => 560,
								'height' => 315,
								'src'    => '//localhost'
							]
						]
					);
				}
			],
			[
				// Multiple "extract" in scrape
				'http://example.invalid/123',
				'<r><EXAMPLE id="456">http://example.invalid/123</EXAMPLE></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = self::populateCache([
						'http://example.invalid/123' => '456'
					]);

					$configurator->MediaEmbed->add(
						'example',
						[
							'host'   => 'example.invalid',
							'scrape' => [
								'match'   => '/./',
								'extract' => ['/foo/', "!^(?'id'[0-9]+)$!"]
							],
							'iframe' => [
								'width'  => 560,
								'height' => 315,
								'src'    => '//localhost'
							]
						]
					);
				}
			],
			[
				// Multiple scrapes
				'http://example.invalid/123',
				'<r><EXAMPLE id="456">http://example.invalid/123</EXAMPLE></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = self::populateCache([
						'http://example.invalid/123' => '456'
					]);

					$configurator->MediaEmbed->add(
						'example',
						[
							'host'   => 'example.invalid',
							'scrape' => [
								[
									'match'   => '/./',
									'extract' => '/foo/'
								],
								[
									'match'   => '/./',
									'extract' => "!^(?'id'[0-9]+)$!"
								]
							],
							'iframe' => [
								'width'  => 560,
								'height' => 315,
								'src'    => '//localhost'
							]
						]
					);
				}
			],
			[
				// Ensure that the hash is ignored when scraping
				'http://example.invalid/123#hash',
				'<r><EXAMPLE id="success">http://example.invalid/123#hash</EXAMPLE></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = self::populateCache([
						'http://example.invalid/123'      => 'success',
						'http://example.invalid/123#hash' => 'error'
					]);

					$configurator->MediaEmbed->add(
						'example',
						[
							'host'   => 'example.invalid',
							'scrape' => [
								[
									'match'   => '/./',
									'extract' => '/(?<id>\\w+)/'
								],
							],
							'iframe' => []
						]
					);
				}
			],
			[
				// Ensure that non-HTTP URLs don't get scraped
				'[media]invalid://example.org/123[/media]',
				'<t>[media]invalid://example.org/123[/media]</t>',
				[],
				function ($configurator)
				{
					$configurator->urlConfig->allowScheme('invalid');
					$configurator->registeredVars['cacheDir'] = self::populateCache([
						'invalid://example.org/123' => '123'
					]);
					$configurator->MediaEmbed->add(
						'example',
						[
							'host'   => 'example.org',
							'scrape' => [
								'match'   => '/./',
								'extract' => "/(?'id'[0-9]+)/"
							],
							'iframe' => ['width' => 1, 'height' => 1, 'src' => 'src']
						]
					);
				}
			],
			[
				// Ensure that we don't scrape the URL if it doesn't match
				'[media]http://example.invalid/123[/media]',
				'<t>[media]http://example.invalid/123[/media]</t>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add(
						'example',
						[
							'host'   => 'example.invalid',
							'scrape' => [
								'match'   => '/XXX/',
								'extract' => "/(?'id'[0-9]+)/"
							],
							'iframe' => ['width' => 1, 'height' => 1, 'src' => 'src']
						]
					);
				}
			],
			[
				'[media]http://foo.example.org/123[/media]',
				'<r><X2 id="123"><s>[media]</s>http://foo.example.org/123<e>[/media]</e></X2></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add(
						'x1',
						[
							'host'    => 'example.org',
							'extract'  => "/(?'id'\\d+)/",
							'iframe' => [
								'width'  => 560,
								'height' => 315,
								'src'    => '//localhost'
							]
						]
					);
					$configurator->MediaEmbed->add(
						'x2',
						[
							'host'    => 'foo.example.org',
							'extract'  => "/(?'id'\\d+)/",
							'iframe' => [
								'width'  => 560,
								'height' => 315,
								'src'    => '//localhost'
							]
						]
					);
				}
			],
			[
				// Ensure no bad things(tm) happen when there's no match
				'[media]http://example.org/123[/media]',
				'<t>[media]http://example.org/123[/media]</t>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add(
						'x2',
						[
							'host'    => 'foo.example.org',
							'extract'  => "/(?'id'\\d+)/",
							'iframe' => [
								'width'  => 560,
								'height' => 315,
								'src'    => '//localhost'
							]
						]
					);
				}
			],
			[
				'[media]http://example.com/baz[/media]',
				'<t>[media]http://example.com/baz[/media]</t>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add(
						'foo',
						[
							'host'    => 'example.com',
							'extract'  => [
								"!example\\.com/(?<foo>foo)!",
								"!example\\.com/(?<bar>bar)!"
							],
							'iframe' => [
								'width'  => 560,
								'height' => 315,
								'src'    => '//localhost'
							]
						]
					);
				}
			],
			[
				'[media]http://example.com/foo[/media]',
				'<r><FOO foo="foo"><s>[media]</s>http://example.com/foo<e>[/media]</e></FOO></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add(
						'foo',
						[
							'host'    => 'example.com',
							'extract'  => [
								"!example\\.com/(?<foo>foo)!",
								"!example\\.com/(?<bar>bar)!"
							],
							'iframe' => [
								'width'  => 560,
								'height' => 315,
								'src'    => '//localhost'
							]
						]
					);
				}
			],
			[
				// @bar is invalid, no match == tag is invalidated
				'[foo bar=BAR]http://example.com/baz[/foo]',
				'<t>[foo bar=BAR]http://example.com/baz[/foo]</t>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add(
						'foo',
						[
							'host'    => 'example.com',
							'extract'  => [
								"!example\\.com/(?<foo>foo)!",
								"!example\\.com/(?<bar>bar)!"
							],
							'iframe' => [
								'width'  => 560,
								'height' => 315,
								'src'    => '//localhost'
							]
						]
					);
				}
			],
			[
				// No match on URL but @bar is valid == tag is kept
				'[foo bar=bar]http://example.com/baz[/foo]',
				'<r><FOO bar="bar"><s>[foo bar=bar]</s>http://example.com/baz<e>[/foo]</e></FOO></r>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('foo', ['defaultAttribute' => 'url', 'contentAttributes' => ['url']]);
					$configurator->MediaEmbed->add(
						'foo',
						[
							'host'    => 'example.com',
							'extract'  => [
								"!example\\.com/(?<foo>foo)!",
								"!example\\.com/(?<bar>bar)!"
							],
							'iframe' => [
								'width'  => 560,
								'height' => 315,
								'src'    => '//localhost'
							]
						]
					);
				}
			],
			[
				'[media=x]..[/media]',
				'<t>[media=x]..[/media]</t>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes;
					$configurator->MediaEmbed->add('foo', ['host' => 'example.invalid']);
					$configurator->tags->add('X');
				}
			],
			[
				'http://localhost/video/123',
				'<r><FOO id="123"><URL url="http://localhost/video/123">http://localhost/video/123</URL></FOO></r>',
				[],
				function ($configurator)
				{
					$configurator->Autolink;
					$configurator->MediaEmbed->add(
						'foo',
						[
							'host'    => 'localhost',
							'extract' => "!localhost/video/(?'id'\\d+)!",
							'iframe'  => ['src' => '//localhost/embed/{@id}']
						]
					);
				}
			],
			[
				'HTTP://example.com/123',
				'<r><FOO id="123">HTTP://example.com/123</FOO></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add(
						'foo',
						[
							'host'    => 'example.com',
							'extract' => '!example\\.com/(?<id>\\d+)!',
							'iframe'  => ['src' => '//localhost/embed/{@id}']
						]
					);
				}
			],
			[
				'[video]http://example.org/123[/video]',
				'<r><EXAMPLE id="123"><s>[video]</s>http://example.org/123<e>[/video]</e></EXAMPLE></r>',
				[],
				function ($configurator)
				{
					$configurator->plugins->load('MediaEmbed', ['tagName' => 'video'])->add(
						'example',
						[
							'host'    => 'example.org',
							'extract' => "/(?'id'\\d+)/",
							'iframe'  => ['src' => '//localhost']
						]
					);
				}
			],
		];
	}

	/**
	* @testdox Scraping tests
	* @dataProvider getScrapingTests
	* @group needs-network
	*/
	public function testScraping()
	{
		$this->runScrapingTest('testParsing', func_get_args());
	}

	public function getScrapingTests()
	{
		return [
			[
				'https://play.acast.com/s/the-taskmaster-podcast/ep31.leemack-s11ep.8',
				'<r><ACAST episode_id="f38da59a-9eb9-42f7-a0bf-a633ef813202" show_id="e3178447-8d7d-556e-bece-5d8f7c4dd539">https://play.acast.com/s/the-taskmaster-podcast/ep31.leemack-s11ep.8</ACAST></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('acast');
				}
			],
			[
				'https://shows.acast.com/thefightingcock/s11e09-gil',
				'<r><ACAST episode_id="611fda6e1e62460013acb5fb" show_id="55daacdd-0c7e-4338-bbc9-57b60dcfd638">https://shows.acast.com/thefightingcock/s11e09-gil</ACAST></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('acast');
				}
			],
			[
				'https://play.acast.com/s/e3178447-8d7d-556e-bece-5d8f7c4dd539/f38da59a-9eb9-42f7-a0bf-a633ef813202?seek=125',
				'<r><ACAST episode_id="f38da59a-9eb9-42f7-a0bf-a633ef813202" show_id="e3178447-8d7d-556e-bece-5d8f7c4dd539" t="125">https://play.acast.com/s/e3178447-8d7d-556e-bece-5d8f7c4dd539/f38da59a-9eb9-42f7-a0bf-a633ef813202?seek=125</ACAST></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('acast');
				}
			],
			[
				'https://audius.co/manilakilla/chet-porter-the-longest-day-ever-manila-killa-edit',
				'<r><AUDIUS track_id="JGMvo">https://audius.co/manilakilla/chet-porter-the-longest-day-ever-manila-killa-edit</AUDIUS></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('audius');
				}
			],
			[
				'https://audius.co/feedbachmusic/album/lights-ii-62147',
				'<r><AUDIUS album_id="eb39M">https://audius.co/feedbachmusic/album/lights-ii-62147</AUDIUS></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('audius');
				}
			],
			[
				'https://audius.co/ezzyland/playlist/audius-asia-%E2%94%81%E2%98%86%EF%BE%9F.%EF%BD%A5%EF%BD%A1%EF%BE%9F-32183',
				'<r><AUDIUS playlist_id="noq27">https://audius.co/ezzyland/playlist/audius-asia-%E2%94%81%E2%98%86%EF%BE%9F.%EF%BD%A5%EF%BD%A1%EF%BE%9F-32183</AUDIUS></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('audius');
				}
			],
//			[
//				'http://proleter.bandcamp.com/album/curses-from-past-times-ep',
//				'<r><BANDCAMP album_id="1122163921">http://proleter.bandcamp.com/album/curses-from-past-times-ep</BANDCAMP></r>',
//				[],
//				function ($configurator)
//				{
//					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
//					$configurator->MediaEmbed->add('bandcamp');
//				}
//			],
//			[
//				'http://proleter.bandcamp.com/track/muhammad-ali',
//				'<r><BANDCAMP album_id="1122163921" track_id="3496015802" track_num="7">http://proleter.bandcamp.com/track/muhammad-ali</BANDCAMP></r>',
//				[],
//				function ($configurator)
//				{
//					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
//					$configurator->MediaEmbed->add('bandcamp');
//				}
//			],
//			[
//				'http://therunons.bandcamp.com/track/still-feel',
//				'<r><BANDCAMP track_id="2146686782">http://therunons.bandcamp.com/track/still-feel</BANDCAMP></r>',
//				[],
//				function ($configurator)
//				{
//					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
//					$configurator->MediaEmbed->add('bandcamp');
//				}
//			],
//			[
//				'http://bleacherreport.com/articles/2415420-creating-a-starting-xi-of-the-most-overrated-players-in-world-football',
//				'<r><BLEACHERREPORT id="dtYjVhdDr5492cyQTjVPDcM--Mg2rJj5">http://bleacherreport.com/articles/2415420-creating-a-starting-xi-of-the-most-overrated-players-in-world-football</BLEACHERREPORT></r>',
//				[],
//				function ($configurator)
//				{
//					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
//					$configurator->MediaEmbed->add('bleacherreport');
//				}
//			],
//			[
//				'http://www.cc.com/video-clips/uu5qz4/key-and-peele-dueling-hats',
//				'<r><COMEDYCENTRAL id="mgid:arc:video:comedycentral.com:bc275e2f-48e3-46d9-b095-0254381497ea">http://www.cc.com/video-clips/uu5qz4/key-and-peele-dueling-hats</COMEDYCENTRAL></r>',
//				[],
//				function ($configurator)
//				{
//					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
//					$configurator->MediaEmbed->add('comedycentral');
//				}
//			],
			[
				'https://www.cnbc.com/video/2019/06/05/carplay-android-auto-alexa-big-tech-gets-into-our-cars.html',
				'<r><CNBC id="7000082990">https://www.cnbc.com/video/2019/06/05/carplay-android-auto-alexa-big-tech-gets-into-our-cars.html</CNBC></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('cnbc');
				}
			],
			[
				'http://www.dumpert.nl/mediabase/6622577/4652b140/r_mi_gaillard_doet_halloween_prank.html',
				'<r><DUMPERT id="6622577/4652b140">http://www.dumpert.nl/mediabase/6622577/4652b140/r_mi_gaillard_doet_halloween_prank.html</DUMPERT></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('dumpert');
				}
			],
//			[
//				'http://8tracks.com/lovinq/headphones-in-world-out',
//				'<r><EIGHTTRACKS id="4982023">http://8tracks.com/lovinq/headphones-in-world-out</EIGHTTRACKS></r>',
//				[],
//				function ($configurator)
//				{
//					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
//					$configurator->MediaEmbed->add('eighttracks');
//				}
//			],
//			[
//				'https://fb.watch/3zYsXdnxjf/',
//				'<r><FACEBOOK id="426049188812854" type="v" user="stvnews">https://fb.watch/3zYsXdnxjf/</FACEBOOK></r>',
//				[],
//				function ($configurator)
//				{
//					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
//					$configurator->MediaEmbed->add('facebook');
//				}
//			],
			[
				'https://flic.kr/p/5wBgXo',
				'<r><FLICKR id="2971804544">https://flic.kr/p/5wBgXo</FLICKR></r>',
				[],
				function ($configurator)
				{
					// Skip during cache preload
					if (isset($_SERVER['CACHE_PRELOAD']))
					{
						$this->markTestSkipped();
					}

					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('flickr');
				}
			],
			[
				'https://gty.im/3232182',
				'(<r><GETTY et="[-\\w]{22}" height="399" id="3232182" sig="[-\\w]{43}=" width="594">https://gty.im/3232182</GETTY></r>)',
				[],
				function ($configurator)
				{
					if (isset($_SERVER['TRAVIS']))
					{
						$this->markTestSkipped();
					}
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('getty');
				},
				null,
				'assertMatchesRegularExpression'
			],
			[
				'https://www.gettyimages.com/detail/3232182',
				'(<r><GETTY et="[-\\w]{22}" height="399" id="3232182" sig="[-\\w]{43}=" width="594">https://www.gettyimages.com/detail/3232182</GETTY></r>)',
				[],
				function ($configurator)
				{
					if (isset($_SERVER['TRAVIS']))
					{
						$this->markTestSkipped();
					}
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('getty');
				},
				null,
				'assertMatchesRegularExpression'
			],
			[
				'https://www.gettyimages.com/detail/news-photo/the-beatles-travel-by-coach-to-the-west-country-for-some-news-photo/3232182',
				'(<r><GETTY et="[-\\w]{22}" height="399" id="3232182" sig="[-\\w]{43}=" width="594">https://www.gettyimages.com/detail/news-photo/the-beatles-travel-by-coach-to-the-west-country-for-some-news-photo/3232182</GETTY></r>)',
				[],
				function ($configurator)
				{
					if (isset($_SERVER['TRAVIS']))
					{
						$this->markTestSkipped();
					}
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('getty');
				},
				null,
				'assertMatchesRegularExpression'
			],
			[
				"https://www.gettyimages.co.jp/detail/%E3%83%8B%E3%83%A5%E3%83%BC%E3%82%B9%E5%86%99%E7%9C%9F/cher-lloyd-promotes-the-new-cd-sorry-im-late-at-nbc-experience-%E3%83%8B%E3%83%A5%E3%83%BC%E3%82%B9%E5%86%99%E7%9C%9F/494028667",
				'(<r><GETTY et="[-\\w]{22}" height="594" id="494028667" sig="[-\\w]{43}=" width="396">https://www.gettyimages.co.jp/detail/%E3%83%8B%E3%83%A5%E3%83%BC%E3%82%B9%E5%86%99%E7%9C%9F/cher-lloyd-promotes-the-new-cd-sorry-im-late-at-nbc-experience-%E3%83%8B%E3%83%A5%E3%83%BC%E3%82%B9%E5%86%99%E7%9C%9F/494028667</GETTY></r>)',
				[],
				function ($configurator)
				{
					if (isset($_SERVER['TRAVIS']))
					{
						$this->markTestSkipped();
					}
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('getty');
				},
				null,
				'assertMatchesRegularExpression'
			],
			[
				"https://www.gettyimages.co.jp/detail/ニュース写真/cher-lloyd-promotes-the-new-cd-sorry-im-late-at-nbc-experience-ニュース写真/494028667",
				'(<r><GETTY et="[-\\w]{22}" height="594" id="494028667" sig="[-\\w]{43}=" width="396">https://www.gettyimages.co.jp/detail/ニュース写真/cher-lloyd-promotes-the-new-cd-sorry-im-late-at-nbc-experience-ニュース写真/494028667</GETTY></r>)',
				[],
				function ($configurator)
				{
					if (isset($_SERVER['TRAVIS']))
					{
						$this->markTestSkipped();
					}
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('getty');
				},
				null,
				'assertMatchesRegularExpression'
			],
			[
				'https://www.gettyimages.com.au/detail/news-photo/sports-journalist-laurent-luyat-plays-with-a-tennis-ball-at-news-photo/534861358',
				'(<r><GETTY et="[-\\w]{22}" height="396" id="534861358" sig="[-\\w]{43}=" width="594">https://www.gettyimages.com.au/detail/news-photo/sports-journalist-laurent-luyat-plays-with-a-tennis-ball-at-news-photo/534861358</GETTY></r>)',
				[],
				function ($configurator)
				{
					if (isset($_SERVER['TRAVIS']))
					{
						$this->markTestSkipped();
					}
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('getty');
				},
				null,
				'assertMatchesRegularExpression'
			],
			[
				'https://gfycat.com/LoathsomeHarmfulJenny',
				'<r><GFYCAT height="534" id="LoathsomeHarmfulJenny" width="950">https://gfycat.com/LoathsomeHarmfulJenny</GFYCAT></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('gfycat');
				}
			],
			[
				'https://gfycat.com/loathsomeharmfuljenny',
				'<r><GFYCAT height="534" id="LoathsomeHarmfulJenny" width="950">https://gfycat.com/loathsomeharmfuljenny</GFYCAT></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('gfycat');
				}
			],
			[
				'https://gfycat.com/gifs/detail/LoathsomeHarmfulJenny',
				'<r><GFYCAT height="534" id="LoathsomeHarmfulJenny" width="950">https://gfycat.com/gifs/detail/LoathsomeHarmfulJenny</GFYCAT></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('gfycat');
				}
			],
			[
				'https://gfycat.com/Test',
				'<r><GFYCAT height="360" id="Test" width="640">https://gfycat.com/Test</GFYCAT></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('gfycat');

					// Save an empty cache file that corresponds to this URL
					$configurator->registeredVars['cacheDir'] = self::populateCache([
						'https://gfycat.com/ifr/Test' => ''
					]);
				}
			],
			[
				'http://giant.gfycat.com/LoathsomeHarmfulJenny.gif',
				'<r><GFYCAT height="534" id="LoathsomeHarmfulJenny" width="950">http://giant.gfycat.com/LoathsomeHarmfulJenny.gif</GFYCAT></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('gfycat');
				}
			],
			[
				'https://j.gifs.com/Y6YZoO.gif',
				'<r><GIFS height="200" id="Y6YZoO" width="200">https://j.gifs.com/Y6YZoO.gif</GIFS></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('gifs');
				}
			],
			[
				'https://media.giphy.com/media/UGUqsAtHh3T1e/source.gif',
				'<r><GIPHY height="160" id="UGUqsAtHh3T1e" type="gif" width="160">https://media.giphy.com/media/UGUqsAtHh3T1e/source.gif</GIPHY></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('giphy');
				}
			],
			[
				'https://giphy.com/gifs/cheezburger-sloth-yawn-FsjDdnIRcroIM',
				'<r><GIPHY height="169" id="FsjDdnIRcroIM" type="gif" width="300">https://giphy.com/gifs/cheezburger-sloth-yawn-FsjDdnIRcroIM</GIPHY></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('giphy');
				}
			],
			[
				'https://giphy.com/videos/quinnxcii-coffee-VG8PNQDwsfOzU4mQAL',
				'<r><GIPHY height="270" id="VG8PNQDwsfOzU4mQAL" type="video" width="480">https://giphy.com/videos/quinnxcii-coffee-VG8PNQDwsfOzU4mQAL</GIPHY></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('giphy');
				}
			],
			[
				'https://i.giphy.com/l41m1wcjpWJZi5w7S.gif',
				'<r><GIPHY height="358" id="l41m1wcjpWJZi5w7S" type="gif" width="500">https://i.giphy.com/l41m1wcjpWJZi5w7S.gif</GIPHY></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('giphy');
				}
			],
			[
				'https://i.giphy.com/l41m1wcjpWJZi5w7S.webp',
				'<r><GIPHY height="358" id="l41m1wcjpWJZi5w7S" type="webp" width="500">https://i.giphy.com/l41m1wcjpWJZi5w7S.webp</GIPHY></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('giphy');
				}
			],
//			[
//				'http://www.hudl.com/v/CVmja',
//				'<r><HUDL athlete="2122944" highlight="5721c090dfe23b2d68a2283b">http://www.hudl.com/v/CVmja</HUDL></r>',
//				[],
//				function ($configurator)
//				{
//					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
//					$configurator->MediaEmbed->add('hudl');
//				}
//			],
//			[
//				'http://www.hulu.com/watch/484180',
//				'<r><HULU id="zPFCgxncn97IFkqEnZ-kRA">http://www.hulu.com/watch/484180</HULU></r>',
//				[],
//				function ($configurator)
//				{
//					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
//					$configurator->MediaEmbed->add('hulu');
//				}
//			],
			[
				'http://www.imdb.com/video/epk/vi387296537/',
				'<r><IMDB id="387296537">http://www.imdb.com/video/epk/vi387296537/</IMDB></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('imdb');
				}
			],
			[
				'http://www.imdb.com/title/tt2294629/videoplayer/vi2482677785',
				'<r><IMDB id="2482677785">http://www.imdb.com/title/tt2294629/videoplayer/vi2482677785</IMDB></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('imdb');
				}
			],
			[
				'http://imgur.com/gallery/9UGCL',
				'<r><IMGUR id="a/9UGCL">http://imgur.com/gallery/9UGCL</IMGUR></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('imgur');
				}
			],
			[
				'http://imgur.com/gallery/49H5yU8',
				'<r><IMGUR id="49H5yU8">http://imgur.com/gallery/49H5yU8</IMGUR></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('imgur');
				}
			],
			[
				'http://imgur.com/cq8lROX',
				'<r><IMGUR id="cq8lROX">http://imgur.com/cq8lROX</IMGUR></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('imgur');
				}
			],
//			[
//				'https://imgur.com/t/current_events/0I30l',
//				'<r><IMGUR id="a/0I30l">https://imgur.com/t/current_events/0I30l</IMGUR></r>',
//				[],
//				function ($configurator)
//				{
//					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
//					$configurator->MediaEmbed->add('imgur');
//				}
//			],
//			[
//				'https://imgur.com/r/animals/dgetQ',
//				'<r><IMGUR id="a/dgetQ">https://imgur.com/r/animals/dgetQ</IMGUR></r>',
//				[],
//				function ($configurator)
//				{
//					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
//					$configurator->MediaEmbed->add('imgur');
//				}
//			],
			[
				'https://archive.org/details/BillGate99',
				'<r><INTERNETARCHIVE height="240" id="BillGate99" width="320">https://archive.org/details/BillGate99</INTERNETARCHIVE></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('internetarchive');
				}
			],
			[
				'https://archive.org/details/DFTS2014-05-30',
				'<r><INTERNETARCHIVE height="50" id="DFTS2014-05-30&amp;playlist=1&amp;twitterv=01" width="300">https://archive.org/details/DFTS2014-05-30</INTERNETARCHIVE></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('internetarchive');
				}
			],
			[
				'https://archive.org/embed/deadco2018-08-25',
				'<r><INTERNETARCHIVE height="50" id="deadco2018-08-25&amp;playlist=1&amp;twitterv=01" width="300">https://archive.org/embed/deadco2018-08-25</INTERNETARCHIVE></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('internetarchive');
				}
			],
//			[
//				'https://www.kaltura.com/tiny/b5tib',
//				'<r><KALTURA entry_id="0_ekjebl7h" partner_id="1581781" sp="158178100" uiconf_id="20490561">https://www.kaltura.com/tiny/b5tib</KALTURA></r>',
//				[],
//				function ($configurator)
//				{
//					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
//					$configurator->MediaEmbed->add('kaltura');
//				}
//			],
//			[
//				'https://vcu.mediaspace.kaltura.com/media/t/1_hu0rc15a',
//				'<r><KALTURA entry_id="1_hu0rc15a" partner_id="1888231" sp="188823100" uiconf_id="28242311">https://vcu.mediaspace.kaltura.com/media/t/1_hu0rc15a</KALTURA></r>',
//				[],
//				function ($configurator)
//				{
//					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
//					$configurator->MediaEmbed->add('kaltura');
//				}
//			],
			[
				'http://video.khl.ru/events/233677',
				'(<r><KHL id="free_\\w+_hd/2_5297335363/\\w+/\\d+">http://video.khl.ru/events/233677</KHL></r>)',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('khl');
				},
				null,
				'assertMatchesRegularExpression'
			],
//			[
//				'http://video.khl.ru/quotes/251237',
//				'(<r><KHL id="free_\\w+_hd/q251237/\\w+/\\d+">http://video.khl.ru/quotes/251237</KHL></r>)',
//				[],
//				function ($configurator)
//				{
//					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
//					$configurator->MediaEmbed->add('khl');
//				},
//				null,
//				'assertMatchesRegularExpression'
//			],
			[
				'http://pexlives.libsyn.com/the-ark-in-space-ep4-commentary-with-el-and-jack',
				'<r><LIBSYN id="8994194">http://pexlives.libsyn.com/the-ark-in-space-ep4-commentary-with-el-and-jack</LIBSYN></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('libsyn');
				}
			],
//			[
//				'https://www.liveleak.com/view?t=sby0J_1571998103',
//				'<r><LIVELEAK id="Szpn_1571998103">https://www.liveleak.com/view?t=sby0J_1571998103</LIVELEAK></r>',
//				[],
//				function ($configurator)
//				{
//					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
//					$configurator->MediaEmbed->add('liveleak');
//				}
//			],
//			[
//				'http://livestre.am/1aHRU',
//				'<r><LIVESTREAM channel="maps_cp" clip_id="pla_d1501f90-438c-401d-98ae-e96ab34a09ae">http://livestre.am/1aHRU</LIVESTREAM></r>',
//				[],
//				function ($configurator)
//				{
//					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
//					$configurator->MediaEmbed->add('livestream');
//				}
//			],
			[
				'https://livestream.com/internetsociety/wsis/videos/107058039',
				'<r><LIVESTREAM account_id="686369" event_id="4588746" video_id="107058039">https://livestream.com/internetsociety/wsis/videos/107058039</LIVESTREAM></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('livestream');
				}
			],
//			[
//				'http://my.mail.ru/corp/auto/video/testdrive/34.html',
//				'<r><MAILRU id="corp/auto/testdrive/34">http://my.mail.ru/corp/auto/video/testdrive/34.html</MAILRU></r>',
//				[],
//				function ($configurator)
//				{
//					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
//					$configurator->MediaEmbed->add('mailru');
//				}
//			],
//			[
//				'http://dev.mrctv.org/videos/cnn-frets-about-tobacco-companies-color-coding-tricks',
//				'<r><MRCTV id="55537">http://dev.mrctv.org/videos/cnn-frets-about-tobacco-companies-color-coding-tricks</MRCTV></r>',
//				[],
//				function ($configurator)
//				{
//					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
//					$configurator->MediaEmbed->add('mrctv');
//				}
//			],
			[
				'http://www.msnbc.com/ronan-farrow-daily/watch/thats-no-moon--300512323725',
				'<r><MSNBC id="mmvo300512323725">http://www.msnbc.com/ronan-farrow-daily/watch/thats-no-moon--300512323725</MSNBC></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('msnbc');
				}
			],
			[
				'http://on.msnbc.com/1qkH62o',
				'<r><MSNBC id="mmvo300512323725">http://on.msnbc.com/1qkH62o</MSNBC></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('msnbc');
				}
			],
//			[
//				'http://video.nationalgeographic.com/tv/changing-earth',
//				'<r><NATGEOVIDEO id="ngc-4MlzV_K8XoTPdXPLx2NOWq2IH410IzpO">http://video.nationalgeographic.com/tv/changing-earth</NATGEOVIDEO></r>',
//				[],
//				function ($configurator)
//				{
//					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
//					$configurator->MediaEmbed->add('natgeochannel');
//					$configurator->MediaEmbed->add('natgeovideo');
//				}
//			],
//			[
//				'http://video.nationalgeographic.com/video/weirdest-superb-lyrebird',
//				'<r><NATGEOVIDEO id="df825c71-a912-476b-be6a-a3fbffed1ae4">http://video.nationalgeographic.com/video/weirdest-superb-lyrebird</NATGEOVIDEO></r>',
//				[],
//				function ($configurator)
//				{
//					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
//					$configurator->MediaEmbed->add('natgeochannel');
//					$configurator->MediaEmbed->add('natgeovideo');
//				}
//			],
			[
				'http://www.nbcsports.com/video/countdown-rio-olympics-what-makes-perfect-performance',
				'<r><NBCSPORTS id="fTQA2MMyx9YO">http://www.nbcsports.com/video/countdown-rio-olympics-what-makes-perfect-performance</NBCSPORTS></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('nbcsports');
				}
			],
			[
				'http://www.npr.org/blogs/goatsandsoda/2015/02/11/385396431/the-50-most-effective-ways-to-transform-the-developing-world',
				'<r><NPR i="385396431" m="385396432">http://www.npr.org/blogs/goatsandsoda/2015/02/11/385396431/the-50-most-effective-ways-to-transform-the-developing-world</NPR></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('npr');
				}
			],
//			[
//				'http://n.pr/1Qky1m5',
//				'<r><NPR i="411271189" m="411271193">http://n.pr/1Qky1m5</NPR></r>',
//				[],
//				function ($configurator)
//				{
//					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
//					$configurator->MediaEmbed->add('npr');
//				}
//			],
			[
				'https://odysee.com/@ozgoals:a/Deni-Juric-Goal-2-0-%C5%A0IBENIK-vs-SLAVEN-Apr21:8',
				'<r><ODYSEE id="8726b01100463c4e254a38c3108ef3e05791aeda" name="Deni-Juric-Goal-2-0-%C5%A0IBENIK-vs-SLAVEN-Apr21">https://odysee.com/@ozgoals:a/Deni-Juric-Goal-2-0-%C5%A0IBENIK-vs-SLAVEN-Apr21:8</ODYSEE></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('odysee');
				}
			],
			[
				'https://howtofail.podbean.com/e/how-to-fail-m-gawdat-c19/',
				'<r><PODBEAN id="gmyyz-d6e065">https://howtofail.podbean.com/e/how-to-fail-m-gawdat-c19/</PODBEAN></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('podbean');
				}
			],
			[
				'https://rumble.com/vaxo02-i-cant-climb-.html',
				'<r><RUMBLE id="v8bhte">https://rumble.com/vaxo02-i-cant-climb-.html</RUMBLE></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('rumble');
				}
			],
//			[
//				'https://rutube.ru/video/0771e73d1d10eee9c43ce31decc1dfce/',
//				'<r><RUTUBE id="4782775">https://rutube.ru/video/0771e73d1d10eee9c43ce31decc1dfce/</RUTUBE></r>',
//				[],
//				function ($configurator)
//				{
//					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
//					$configurator->MediaEmbed->add('rutube');
//				}
//			],
			[
				'http://www.slideshare.net/Slideshare/10-million-uploads-our-favorites',
				'<r><SLIDESHARE id="21112125">http://www.slideshare.net/Slideshare/10-million-uploads-our-favorites</SLIDESHARE></r>',
				[],
				function ($configurator)
				{
					if (isset($_SERVER['TRAVIS']))
					{
						$this->markTestSkipped('SlideShare blocks requests from Travis containers');
					}
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('slideshare');
				}
			],
//			[
//				'https://soundcloud.com/topdawgent/i-1/s-GT9Cd',
//				'<r><SOUNDCLOUD id="topdawgent/i-1/s-GT9Cd" secret_token="s-GT9Cd" track_id="168988860">https://soundcloud.com/topdawgent/i-1/s-GT9Cd</SOUNDCLOUD></r>',
//				[],
//				function ($configurator)
//				{
//					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
//					$configurator->MediaEmbed->add('soundcloud');
//				}
//			],
			[
				'https://soundcloud.com/andrewbird/three-white-horses',
				'<r><SOUNDCLOUD id="andrewbird/three-white-horses" track_id="59509713">https://soundcloud.com/andrewbird/three-white-horses</SOUNDCLOUD></r>',
				[],
				function ($configurator)
				{
					if (isset($_SERVER['TRAVIS']))
					{
						$this->markTestSkipped('SoundCloud does not like requests from Travis containers');
					}
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('soundcloud');
				}
			],
			[
				'Https://soundcloud.com/andrewbird/three-white-horses',
				'<r><SOUNDCLOUD id="andrewbird/three-white-horses" track_id="59509713">Https://soundcloud.com/andrewbird/three-white-horses</SOUNDCLOUD></r>',
				[],
				function ($configurator)
				{
					if (isset($_SERVER['TRAVIS']))
					{
						$this->markTestSkipped('SoundCloud does not like requests from Travis containers');
					}
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('soundcloud');
				}
			],
			[
				'https://soundcloud.com/user-56185579/sets/deep-impact-for-u-he-zebra-2',
				'<r><SOUNDCLOUD id="user-56185579/sets/deep-impact-for-u-he-zebra-2" playlist_id="1227667873" track_id="1009549597">https://soundcloud.com/user-56185579/sets/deep-impact-for-u-he-zebra-2</SOUNDCLOUD></r>',
				[],
				function ($configurator)
				{
					if (isset($_SERVER['TRAVIS']))
					{
						$this->markTestSkipped('SoundCloud does not like requests from Travis containers');
					}
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('soundcloud');
				}
			],
			[
				'https://www.sporcle.com/games/eyes355/find-five-countries',
				'<r><SPORCLE id="15d32a898365">https://www.sporcle.com/games/eyes355/find-five-countries</SPORCLE></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('sporcle');
				}
			],
			[
				'https://www.sportsnet.ca/soccer/west-ham-2-hull-2/',
				'<r><SPORTSNET id="3786409870001">https://www.sportsnet.ca/soccer/west-ham-2-hull-2/</SPORTSNET></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('sportsnet');
				}
			],
//			[
//				'https://link.tospotify.com/kpRZPpEGnbb',
//				'<r><SPOTIFY id="playlist/12UZc2MhOI29wE7eBgmKwi">https://link.tospotify.com/kpRZPpEGnbb</SPOTIFY></r>',
//				[],
//				function ($configurator)
//				{
//					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
//					$configurator->MediaEmbed->add('spotify');
//				}
//			],
			[
				'https://www.spreaker.com/show/2-up-front_1',
				'<r><SPREAKER show_id="1374587">https://www.spreaker.com/show/2-up-front_1</SPREAKER></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('spreaker');
				}
			],
			[
				'https://www.spreaker.com/user/publichousemedia/2-up-front-119-final-show-peter-wilt-rac',
				'<r><SPREAKER episode_id="12449095">https://www.spreaker.com/user/publichousemedia/2-up-front-119-final-show-peter-wilt-rac</SPREAKER></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('spreaker');
				}
			],
			[
				'https://www.stitcher.com/show/national-geographics-overheard/episode/chasing-the-worlds-largest-tornado-79283587',
				'<r><STITCHER eid="79283587" fid="416389">https://www.stitcher.com/show/national-geographics-overheard/episode/chasing-the-worlds-largest-tornado-79283587</STITCHER></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('stitcher');
				}
			],
//			[
//				'https://www.stitcher.com/s?eid=79283587',
//				'<r><STITCHER eid="79283587" fid="416389">https://www.stitcher.com/s?eid=79283587</STITCHER></r>',
//				[],
//				function ($configurator)
//				{
//					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
//					$configurator->MediaEmbed->add('stitcher');
//				}
//			],
			[
				'http://teamcoco.com/video/serious-jibber-jabber-a-scott-berg-full-episode',
				'<r><TEAMCOCO id="73784">http://teamcoco.com/video/serious-jibber-jabber-a-scott-berg-full-episode</TEAMCOCO></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('teamcoco');
				}
			],
			[
				'https://vm.tiktok.com/ZMeNud8UD/',
				'<r><TIKTOK id="6930698976291261702">https://vm.tiktok.com/ZMeNud8UD/</TIKTOK></r>',
				[],
				function ($configurator)
				{
					if (isset($_SERVER['TRAVIS']))
					{
						$this->markTestSkipped('No TikTok test on Travis');
					}
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('tiktok');
				}
			],
//			[
//				'http://www.traileraddict.com/robocop-2013/tv-spot-meet-the-future-ii',
//				'<r><TRAILERADDICT id="85253">http://www.traileraddict.com/robocop-2013/tv-spot-meet-the-future-ii</TRAILERADDICT></r>',
//				[],
//				function ($configurator)
//				{
//					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
//					$configurator->MediaEmbed->add('traileraddict');
//				}
//			],
			[
				'https://staff.tumblr.com/post/180624227620/',
				'<r><TUMBLR did="781ba97d76586f567b41960375dac9606da2cc7e" id="180624227620" key="r_BTLFg8QzTPUMVSbe3vbg" name="staff">https://staff.tumblr.com/post/180624227620/</TUMBLR></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('tumblr');
				}
			],
			[
				'https://247sports.com/Player/LeSean-McCoy-25234/',
				'<r><TWENTYFOURSEVENSPORTS player_id="149349">https://247sports.com/Player/LeSean-McCoy-25234/</TWENTYFOURSEVENSPORTS></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('twentyfoursevensports');
				}
			],
			[
				'https://247sports.com/Video/FFT-Week-12-QBRB-Start-or-Sit-1122-8838631/',
				'<r><TWENTYFOURSEVENSPORTS video_id="1378030659860">https://247sports.com/Video/FFT-Week-12-QBRB-Start-or-Sit-1122-8838631/</TWENTYFOURSEVENSPORTS></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('twentyfoursevensports');
				}
			],
			[
				'http://www.ustream.tv/channel/ps4-ustream-gameplay',
				'<r><USTREAM cid="16234409">http://www.ustream.tv/channel/ps4-ustream-gameplay</USTREAM></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('ustream');
				}
			],
			[
				'https://vk.com/video121599878_165723901',
				'<r><VK hash="e06b0878046e1d32" oid="121599878" vid="165723901">https://vk.com/video121599878_165723901</VK></r>',
				[],
				function ($configurator)
				{
					if (isset($_SERVER['TRAVIS']))
					{
						$this->markTestSkipped('No VK test on Travis');
					}
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('vk');
				}
			],
			[
				'https://thoughtworks.wistia.com/medias/b6al55s35k',
				'<r><WISTIA id="b6al55s35k">https://thoughtworks.wistia.com/medias/b6al55s35k</WISTIA></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('wistia');
				}
			],
			[
				'https://wistia.com/series/talking-too-loud?wchannelid=9c5u5vvz1d&wmediaid=od5qgyo2uy',
				'<r><WISTIA id="od5qgyo2uy" type="audio">https://wistia.com/series/talking-too-loud?wchannelid=9c5u5vvz1d&amp;wmediaid=od5qgyo2uy</WISTIA></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('wistia');
				}
			],
			[
				'https://www.worldstarhiphop.com/videos/video.php?v=wshhZ8F22UtJ8sLHdja0',
				'<r><WSHH id="63133">https://www.worldstarhiphop.com/videos/video.php?v=wshhZ8F22UtJ8sLHdja0</WSHH></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('wshh');
				}
			],
			[
				'https://m.worldstarhiphop.com/video.php?v=wshh2SXFFe7W14DqQx61',
				'<r><WSHH id="63175">https://m.worldstarhiphop.com/video.php?v=wshh2SXFFe7W14DqQx61</WSHH></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('wshh');
				}
			],
			[
				'https://worldstar.com/video.php?v=wshhn7nmk3033k8m66ZG',
				'<r><WSHH id="164634">https://worldstar.com/video.php?v=wshhn7nmk3033k8m66ZG</WSHH></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('wshh');
				}
			],
			[
				'http://on.wsj.com/1MJvx06',
				'<r><WSJ id="9E476D54-6A60-4F3F-ABC1-411014552DE6">http://on.wsj.com/1MJvx06</WSJ></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('wsj');
				}
			],
		];
	}

	/**
	* @testdox Scraping+rendering tests
	* @dataProvider getScrapingRenderingTests
	* @group needs-network
	*/
	public function testScrapingRendering()
	{
		$this->runScrapingTest('testRendering', func_get_args());
	}

	public function getScrapingRenderingTests()
	{
		return [
//			[
//				'http://proleter.bandcamp.com/album/curses-from-past-times-ep',
//				'<span data-s9e-mediaembed="bandcamp" style="display:inline-block;width:100%;max-width:400px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:100%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//bandcamp.com/EmbeddedPlayer/size=large/minimal=true/album=1122163921"></iframe></span></span>',
//				[],
//				function ($configurator)
//				{
//					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
//					$configurator->MediaEmbed->add('bandcamp');
//				}
//			],
//			[
//				'http://proleter.bandcamp.com/track/muhammad-ali',
//				'<span data-s9e-mediaembed="bandcamp" style="display:inline-block;width:100%;max-width:400px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:100%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//bandcamp.com/EmbeddedPlayer/size=large/minimal=true/album=1122163921/t=7"></iframe></span></span>',
//				[],
//				function ($configurator)
//				{
//					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
//					$configurator->MediaEmbed->add('bandcamp');
//				}
//			],
//			[
//				'http://therunons.bandcamp.com/track/still-feel',
//				'<span data-s9e-mediaembed="bandcamp" style="display:inline-block;width:100%;max-width:400px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:100%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//bandcamp.com/EmbeddedPlayer/size=large/minimal=true/track=2146686782"></iframe></span></span>',
//				[],
//				function ($configurator)
//				{
//					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
//					$configurator->MediaEmbed->add('bandcamp');
//				}
//			],
//			[
//				'http://www.comedycentral.com/video-clips/uu5qz4/key-and-peele-dueling-hats',
//				'<span data-s9e-mediaembed="comedycentral" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" scrolling="no" src="//media.mtvnservices.com/embed/mgid:arc:video:comedycentral.com:bc275e2f-48e3-46d9-b095-0254381497ea" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span></span>',
//				[],
//				function ($configurator)
//				{
//					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
//					$configurator->MediaEmbed->add('comedycentral');
//				}
//			],
			[
				'https://www.gettyimages.com/detail/3232182',
				'(<span data-s9e-mediaembed="getty" style="display:inline-block;width:100%;max-width:594px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:67\\.1717171717172%"><iframe allowfullscreen="" loading="lazy" scrolling="no" src="//embed\\.gettyimages\\.com/embed/3232182\\?et=[-\w]{22}&amp;tld=com&amp;sig=[-\w]{43}=&amp;caption=false&amp;ver=1" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span></span>)',
				[],
				function ($configurator)
				{
					if (isset($_SERVER['TRAVIS']))
					{
						$this->markTestSkipped();
					}
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('getty');
				},
				'assertMatchesRegularExpression'
			],
			[
				'https://gfycat.com/LoathsomeHarmfulJenny',
				'<span data-s9e-mediaembed="gfycat" style="display:inline-block;width:100%;max-width:950px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:60.8421052631579%;padding-bottom:calc(56.2105263157895% + 44px)"><iframe allowfullscreen="" loading="lazy" scrolling="no" src="//gfycat.com/ifr/LoathsomeHarmfulJenny?hd=1" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('gfycat');
				}
			],
			[
				'https://gfycat.com/ifr/LoathsomeHarmfulJenny',
				'<span data-s9e-mediaembed="gfycat" style="display:inline-block;width:100%;max-width:950px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:60.8421052631579%;padding-bottom:calc(56.2105263157895% + 44px)"><iframe allowfullscreen="" loading="lazy" scrolling="no" src="//gfycat.com/ifr/LoathsomeHarmfulJenny?hd=1" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('gfycat');
				}
			],
			[
				'https://giphy.com/gifs/cheezburger-sloth-yawn-FsjDdnIRcroIM',
				'<span data-s9e-mediaembed="giphy" style="display:inline-block;width:100%;max-width:300px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.3333333333333%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//giphy.com/embed/FsjDdnIRcroIM"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('giphy');
				}
			],
			[
				'https://giphy.com/videos/quinnxcii-coffee-VG8PNQDwsfOzU4mQAL',
				'<span data-s9e-mediaembed="giphy" style="display:inline-block;width:100%;max-width:480px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//giphy.com/embed/VG8PNQDwsfOzU4mQAL/video"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('giphy');
				}
			],
			[
				'https://archive.org/details/BillGate99',
				'<span data-s9e-mediaembed="internetarchive" style="display:inline-block;width:100%;max-width:320px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:75%"><iframe allowfullscreen="" loading="lazy" scrolling="no" src="https://archive.org/embed/BillGate99" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('internetarchive');
				}
			],
			[
				'https://archive.org/details/DFTS2014-05-30',
				'<iframe data-s9e-mediaembed="internetarchive" allowfullscreen="" loading="lazy" scrolling="no" src="https://archive.org/embed/DFTS2014-05-30&amp;playlist=1&amp;twitterv=01&amp;list_height=150" style="border:0;height:170px;max-width:640px;width:100%"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('internetarchive');
				}
			],
			[
				'http://www.msnbc.com/ronan-farrow-daily/watch/thats-no-moon--300512323725',
				'<span data-s9e-mediaembed="msnbc" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" scrolling="no" src="https://www.msnbc.com/msnbc/embedded-video/mmvo300512323725" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('msnbc');
				}
			],
			[
				'https://soundcloud.com/andrewbird/three-white-horses',
				'<iframe data-s9e-mediaembed="soundcloud" allowfullscreen="" loading="lazy" scrolling="no" src="https://w.soundcloud.com/player/?url=https%3A//api.soundcloud.com/tracks/59509713&amp;secret_token=" style="border:0;height:166px;max-width:900px;width:100%"></iframe>',
				[],
				function ($configurator)
				{
					if (isset($_SERVER['TRAVIS']))
					{
						$this->markTestSkipped('SoundCloud does not like requests from Travis containers');
					}
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('soundcloud');
				}
			],
			[
				'https://www.spreaker.com/show/2-up-front_1',
				'<iframe data-s9e-mediaembed="spreaker" allowfullscreen="" loading="lazy" scrolling="no" src="https://widget.spreaker.com/player?episode_id=&amp;show_id=1374587&amp;theme=" style="border:0;height:400px;max-width:900px;width:100%"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('spreaker');
				}
			],
			[
				'https://www.spreaker.com/user/publichousemedia/2-up-front-119-final-show-peter-wilt-rac',
				'<iframe data-s9e-mediaembed="spreaker" allowfullscreen="" loading="lazy" scrolling="no" src="https://widget.spreaker.com/player?episode_id=12449095&amp;show_id=&amp;theme=" style="border:0;height:200px;max-width:900px;width:100%"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('spreaker');
				}
			],
			[
				'http://www.ustream.tv/channel/ps4-ustream-gameplay',
				'<span data-s9e-mediaembed="ustream" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//www.ustream.tv/embed/16234409?html5ui"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('ustream');
				}
			],
		];
	}

	public function getParsingTests()
	{
		return [
			[
				'http://abcnews.go.com/US/video/missing-malaysian-flight-words-revealed-hunt-continues-hundreds-22880799',
				'<r><ABCNEWS id="22880799">http://abcnews.go.com/US/video/missing-malaysian-flight-words-revealed-hunt-continues-hundreds-22880799</ABCNEWS></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('abcnews');
				}
			],
			[
				'http://abcnews.go.com/Politics/video/special-live-1-14476486',
				'<r><ABCNEWS id="14476486">http://abcnews.go.com/Politics/video/special-live-1-14476486</ABCNEWS></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('abcnews');
				}
			],
			[
				'http://abcnews.go.com/video/embed?id=45798660',
				'<r><ABCNEWS id="45798660">http://abcnews.go.com/video/embed?id=45798660</ABCNEWS></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('abcnews');
				}
			],
			[
				'http://www.amazon.ca/gp/product/B00GQT1LNO/',
				'<r><AMAZON id="B00GQT1LNO" tld="ca">http://www.amazon.ca/gp/product/B00GQT1LNO/</AMAZON></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('amazon');
				}
			],
			[
				'http://www.amazon.co.jp/gp/product/B003AKZ6I8/',
				'<r><AMAZON id="B003AKZ6I8" tld="jp">http://www.amazon.co.jp/gp/product/B003AKZ6I8/</AMAZON></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('amazon');
				}
			],
			[
				'http://www.amazon.co.uk/gp/product/B00BET0NR6/',
				'<r><AMAZON id="B00BET0NR6" tld="uk">http://www.amazon.co.uk/gp/product/B00BET0NR6/</AMAZON></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('amazon');
				}
			],
			[
				'http://www.amazon.com/dp/B002MUC0ZY',
				'<r><AMAZON id="B002MUC0ZY">http://www.amazon.com/dp/B002MUC0ZY</AMAZON></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('amazon');
				}
			],
			[
				'http://www.amazon.com/The-BeerBelly-200-001-80-Ounce-Belly/dp/B001RB2CXY/',
				'<r><AMAZON id="B001RB2CXY">http://www.amazon.com/The-BeerBelly-200-001-80-Ounce-Belly/dp/B001RB2CXY/</AMAZON></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('amazon');
				}
			],
			[
				'http://www.amazon.com/gp/product/B0094H8H7I',
				'<r><AMAZON id="B0094H8H7I">http://www.amazon.com/gp/product/B0094H8H7I</AMAZON></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('amazon');
				}
			],
			[
				'http://www.amazon.de/Netgear-WN3100RP-100PES-Repeater-integrierte-Steckdose/dp/B00ET2LTE6/',
				'<r><AMAZON id="B00ET2LTE6" tld="de">http://www.amazon.de/Netgear-WN3100RP-100PES-Repeater-integrierte-Steckdose/dp/B00ET2LTE6/</AMAZON></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('amazon');
				}
			],
			[
				'http://www.amazon.es/Vans-OLD-SKOOL-BLACK-WHITE/dp/B000R3QPEA/',
				'<r><AMAZON id="B000R3QPEA" tld="es">http://www.amazon.es/Vans-OLD-SKOOL-BLACK-WHITE/dp/B000R3QPEA/</AMAZON></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('amazon');
				}
			],
			[
				'http://www.amazon.fr/Vans-Authentic-Baskets-mixte-adulte/dp/B005NIKPAY/',
				'<r><AMAZON id="B005NIKPAY" tld="fr">http://www.amazon.fr/Vans-Authentic-Baskets-mixte-adulte/dp/B005NIKPAY/</AMAZON></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('amazon');
				}
			],
			[
				'http://www.amazon.in/Vans-Unisex-Authentic-Midnight-Sneakers/dp/B01I3LNWQG/',
				'<r><AMAZON id="B01I3LNWQG" tld="in">http://www.amazon.in/Vans-Unisex-Authentic-Midnight-Sneakers/dp/B01I3LNWQG/</AMAZON></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('amazon');
				}
			],
			[
				'http://www.amazon.it/gp/product/B00JGOMIP6/',
				'<r><AMAZON id="B00JGOMIP6" tld="it">http://www.amazon.it/gp/product/B00JGOMIP6/</AMAZON></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('amazon');
				}
			],
			[
				'https://www.amazon.co.uk/gp/help/customer/display.html?nodeId=201834890',
				'<t>https://www.amazon.co.uk/gp/help/customer/display.html?nodeId=201834890</t>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('amazon');
				}
			],
			[
				'https://anchor.fm/blockhash-exploring-the-blockchain/episodes/Daniel-Gouldman---CEO-of-Ternio-e8vruo/a-a11ehhg',
				'<r><ANCHOR id="e8vruo">https://anchor.fm/blockhash-exploring-the-blockchain/episodes/Daniel-Gouldman---CEO-of-Ternio-e8vruo/a-a11ehhg</ANCHOR></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('anchor');
				}
			],
			[
				'https://podcasts.apple.com/dk/podcast/say-his-name-george-floyd-retssagen-begynder/id1429569036',
				'<r><APPLEPODCASTS country="dk" podcast_id="1429569036">https://podcasts.apple.com/dk/podcast/say-his-name-george-floyd-retssagen-begynder/id1429569036</APPLEPODCASTS></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('applepodcasts');
				}
			],
			[
				'https://podcasts.apple.com/dk/podcast/say-his-name-george-floyd-retssagen-begynder/id1429569036?i=1000514458995',
				'<r><APPLEPODCASTS country="dk" episode_id="1000514458995" podcast_id="1429569036">https://podcasts.apple.com/dk/podcast/say-his-name-george-floyd-retssagen-begynder/id1429569036?i=1000514458995</APPLEPODCASTS></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('applepodcasts');
				}
			],
			[
				'http://audioboo.fm/boos/2439994-deadline-day-update',
				'<r><AUDIOBOOM id="2439994">http://audioboo.fm/boos/2439994-deadline-day-update</AUDIOBOOM></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('audioboom');
				}
			],
			[
				'https://audioboom.com/posts/2493448-robert-patrick',
				'<r><AUDIOBOOM id="2493448">https://audioboom.com/posts/2493448-robert-patrick</AUDIOBOOM></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('audioboom');
				}
			],
			[
				'http://www.audiomack.com/song/random-2/buy-the-world-final-1',
				'<r><AUDIOMACK artist="random-2" mode="song" title="buy-the-world-final-1">http://www.audiomack.com/song/random-2/buy-the-world-final-1</AUDIOMACK></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('audiomack');
				}
			],
			[
				'http://www.audiomack.com/album/hz-global/double-a-side-vol3',
				'<r><AUDIOMACK artist="hz-global" mode="album" title="double-a-side-vol3">http://www.audiomack.com/album/hz-global/double-a-side-vol3</AUDIOMACK></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('audiomack');
				}
			],
			[
				'https://www.bbc.co.uk/news/av/embed/p0783sg7/48125671',
				'<r><BBCNEWS id="p0783sg7/48125671">https://www.bbc.co.uk/news/av/embed/p0783sg7/48125671</BBCNEWS></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('bbcnews');
				}
			],
			[
				'https://www.bbc.com/news/uk-england-london-48651131',
				'<t>https://www.bbc.com/news/uk-england-london-48651131</t>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('bbcnews');
				}
			],
			[
				'http://www.bbc.com/news/video_and_audio/must_see/42847060/calls-to-clean-off-banksy-mural-in-hull',
				'<r><BBCNEWS id="42847060">http://www.bbc.com/news/video_and_audio/must_see/42847060/calls-to-clean-off-banksy-mural-in-hull</BBCNEWS></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('bbcnews');
				}
			],
			[
				'https://www.bbc.co.uk/news/av/world-africa-48141248/fifteen-year-old-nigerian-builds-small-scale-construction-machines',
				'<r><BBCNEWS id="48141248">https://www.bbc.co.uk/news/av/world-africa-48141248/fifteen-year-old-nigerian-builds-small-scale-construction-machines</BBCNEWS></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('bbcnews');
				}
			],
			[
				'https://www.bbc.co.uk/news/live/world-54505193',
				'<t>https://www.bbc.co.uk/news/live/world-54505193</t>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('bbcnews');
				}
			],
			[
				'https://www.bitchute.com/video/y9AejeZ2vD0/',
				'<r><BITCHUTE id="y9AejeZ2vD0">https://www.bitchute.com/video/y9AejeZ2vD0/</BITCHUTE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('bitchute');
				}
			],
			[
				'https://www.bitchute.com/embed/k1N2uGB-Xls/',
				'<r><BITCHUTE id="k1N2uGB-Xls">https://www.bitchute.com/embed/k1N2uGB-Xls/</BITCHUTE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('bitchute');
				}
			],
			[
				'https://link.brightcove.com/services/player/bcpid4773906090001?bckey=AQ~~,AAAAAA0Xi_s~,r1xMuE8k5Nyz5IyYc0Hzhl5eZ5cEEvlm&bctid=4815779906001',
				'<r><BRIGHTCOVE bckey="AQ~~,AAAAAA0Xi_s~,r1xMuE8k5Nyz5IyYc0Hzhl5eZ5cEEvlm" bcpid="4773906090001" bctid="4815779906001">https://link.brightcove.com/services/player/bcpid4773906090001?bckey=AQ~~,AAAAAA0Xi_s~,r1xMuE8k5Nyz5IyYc0Hzhl5eZ5cEEvlm&amp;bctid=4815779906001</BRIGHTCOVE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('brightcove');
				}
			],
			[
				'https://players.brightcove.net/219646971/default_default/index.html?videoId=4815779906001',
				'<r><BRIGHTCOVE bcpid="219646971" bctid="4815779906001">https://players.brightcove.net/219646971/default_default/index.html?videoId=4815779906001</BRIGHTCOVE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('brightcove');
				}
			],
			[
				'https://on-the-cuff.castos.com/player/215060',
				'<r><CASTOS host="on-the-cuff" id="215060">https://on-the-cuff.castos.com/player/215060</CASTOS></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('castos');
				}
			],
			[
				'http://www.cbsnews.com/video/watch/?id=50156501n',
				'<r><CBSNEWS id="50156501">http://www.cbsnews.com/video/watch/?id=50156501n</CBSNEWS></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('cbsnews');
				}
			],
			[
				'http://www.cbsnews.com/videos/is-carbonated-water-a-healthy-option',
				'<r><CBSNEWS id="is-carbonated-water-a-healthy-option">http://www.cbsnews.com/videos/is-carbonated-water-a-healthy-option</CBSNEWS></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('cbsnews');
				}
			],
			[
				'https://clyp.it/0hdvyehn',
				'<r><CLYP id="0hdvyehn">https://clyp.it/0hdvyehn</CLYP></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('clyp');
				}
			],
			[
				'https://clyp.it/user/2sww5vaw',
				'<t>https://clyp.it/user/2sww5vaw</t>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('clyp');
				}
			],
			[
				'http://video.cnbc.com/gallery/?video=3000269279',
				'<r><CNBC id="3000269279">http://video.cnbc.com/gallery/?video=3000269279</CNBC></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('cnbc');
				}
			],
			[
				'http://edition.cnn.com/videos/tv/2015/06/09/airplane-yoga-rachel-crane-ts-orig.cnn',
				'<r><CNN id="tv/2015/06/09/airplane-yoga-rachel-crane-ts-orig.cnn">http://edition.cnn.com/videos/tv/2015/06/09/airplane-yoga-rachel-crane-ts-orig.cnn</CNN></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('cnn');
				}
			],
			[
				'http://us.cnn.com/video/data/2.0/video/bestoftv/2013/10/23/vo-nr-prince-george-christening-arrival.cnn.html',
				'<r><CNN id="bestoftv/2013/10/23/vo-nr-prince-george-christening-arrival.cnn">http://us.cnn.com/video/data/2.0/video/bestoftv/2013/10/23/vo-nr-prince-george-christening-arrival.cnn.html</CNN></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('cnn');
				}
			],
			[
				'http://us.cnn.com/video/data/2.0/video/bestoftv/2013/10/23/vo-nr-prince-george-christening-arrival.cnn.html',
				'<r><CNN id="bestoftv/2013/10/23/vo-nr-prince-george-christening-arrival.cnn">http://us.cnn.com/video/data/2.0/video/bestoftv/2013/10/23/vo-nr-prince-george-christening-arrival.cnn.html</CNN></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('cnn');
					$configurator->MediaEmbed->add('cnnmoney');
				}
			],
			[
				'http://www.cnn.com/video/data/2.0/video/us/2014/09/01/lead-dnt-brown-property-seizures.cnn.html',
				'<r><CNN id="us/2014/09/01/lead-dnt-brown-property-seizures.cnn">http://www.cnn.com/video/data/2.0/video/us/2014/09/01/lead-dnt-brown-property-seizures.cnn.html</CNN></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('cnn');
					$configurator->MediaEmbed->add('cnnmoney');
				}
			],
			[
				'http://money.cnn.com/video/technology/2014/05/20/t-twitch-vp-on-future.cnnmoney/',
				'<r><CNNMONEY id="technology/2014/05/20/t-twitch-vp-on-future.cnnmoney">http://money.cnn.com/video/technology/2014/05/20/t-twitch-vp-on-future.cnnmoney/</CNNMONEY></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('cnnmoney');
				}
			],
			[
				'http://money.cnn.com/video/technology/2014/05/20/t-twitch-vp-on-future.cnnmoney/',
				'<r><CNNMONEY id="technology/2014/05/20/t-twitch-vp-on-future.cnnmoney">http://money.cnn.com/video/technology/2014/05/20/t-twitch-vp-on-future.cnnmoney/</CNNMONEY></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('cnn');
					$configurator->MediaEmbed->add('cnnmoney');
				}
			],
			[
				'https://codepen.io/ainalem/pen/wvKOEMV',
				'<r><CODEPEN id="wvKOEMV" user="ainalem">https://codepen.io/ainalem/pen/wvKOEMV</CODEPEN></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('codepen');
				}
			],
			[
				'http://coub.com/view/6veusoty',
				'<r><COUB id="6veusoty">http://coub.com/view/6veusoty</COUB></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('coub');
				}
			],
			[
				'http://www.dailymotion.com/video/x222z1',
				'<r><DAILYMOTION id="x222z1">http://www.dailymotion.com/video/x222z1</DAILYMOTION></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('dailymotion');
				}
			],
			[
				'http://www.dailymotion.com/user/Dailymotion/2#video=x222z1',
				'<r><DAILYMOTION id="x222z1">http://www.dailymotion.com/user/Dailymotion/2#video=x222z1</DAILYMOTION></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('dailymotion');
				}
			],
			[
				'http://games.dailymotion.com/live/x15gjhi',
				'<r><DAILYMOTION id="x15gjhi">http://games.dailymotion.com/live/x15gjhi</DAILYMOTION></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('dailymotion');
				}
			],
			[
				'http://www.dailymotion.com/video/x5e9eog?start=90',
				'<r><DAILYMOTION id="x5e9eog" t="90">http://www.dailymotion.com/video/x5e9eog?start=90</DAILYMOTION></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('dailymotion');
				}
			],
			[
				'http://dai.ly/x5e9eog',
				'<r><DAILYMOTION id="x5e9eog">http://dai.ly/x5e9eog</DAILYMOTION></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('dailymotion');
				}
			],
			[
				'http://www.dailymotion.com/related/2344952/video/x12w88_le-peril-jeune_fun',
				'<r><DAILYMOTION id="x12w88">http://www.dailymotion.com/related/2344952/video/x12w88_le-peril-jeune_fun</DAILYMOTION></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('dailymotion');
				}
			],
			[
				'http://www.democracynow.org/2014/7/2/dn_at_almedalen_week_at_swedens',
				'<r><DEMOCRACYNOW id="2014/7/2/dn_at_almedalen_week_at_swedens">http://www.democracynow.org/2014/7/2/dn_at_almedalen_week_at_swedens</DEMOCRACYNOW></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('democracynow');
				}
			],
			[
				'http://www.democracynow.org/blog/2015/3/13/part_2_bruce_schneier_on_the',
				'<r><DEMOCRACYNOW id="blog/2015/3/13/part_2_bruce_schneier_on_the">http://www.democracynow.org/blog/2015/3/13/part_2_bruce_schneier_on_the</DEMOCRACYNOW></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('democracynow');
				}
			],
			[
				'http://www.democracynow.org/shows/2006/2/20',
				'<r><DEMOCRACYNOW id="shows/2006/2/20">http://www.democracynow.org/shows/2006/2/20</DEMOCRACYNOW></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('democracynow');
				}
			],
			[
				'http://8tracks.com/midna/2242699',
				'<r><EIGHTTRACKS id="2242699">http://8tracks.com/midna/2242699</EIGHTTRACKS></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('eighttracks');
				}
			],
			[
				'http://www.espn.com/video/clip?id=17474659',
				'<r><ESPN id="17474659">http://www.espn.com/video/clip?id=17474659</ESPN></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('espn');
				}
			],
			[
				'http://www.espn.com/video/clip/_/id/17474659/categoryid/2564308',
				'<r><ESPN id="17474659">http://www.espn.com/video/clip/_/id/17474659/categoryid/2564308</ESPN></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('espn');
				}
			],
			[
				'http://www.espn.com/espnw/video/13887284/kyrgios-angry-code-violation-almost-hitting-ref',
				'<r><ESPN id="13887284">http://www.espn.com/espnw/video/13887284/kyrgios-angry-code-violation-almost-hitting-ref</ESPN></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('espn');
				}
			],
			[
				'http://broadband.espn.go.com/video/clip?id=17481969',
				'<r><ESPN id="17481969">http://broadband.espn.go.com/video/clip?id=17481969</ESPN></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('espn');
				}
			],
			[
				'https://www.facebook.com/photo.php?v=10100658170103643&set=vb.20531316728&type=3&theater',
				'<r><FACEBOOK id="10100658170103643">https://www.facebook.com/photo.php?v=10100658170103643&amp;set=vb.20531316728&amp;type=3&amp;theater</FACEBOOK></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('facebook');
				}
			],
			[
				'https://www.facebook.com/video/video.php?v=10150451523596807',
				'<r><FACEBOOK id="10150451523596807" type="v">https://www.facebook.com/video/video.php?v=10150451523596807</FACEBOOK></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('facebook');
				}
			],
			[
				'https://www.facebook.com/watch/?v=224353158889229',
				'<r><FACEBOOK id="224353158889229" type="v">https://www.facebook.com/watch/?v=224353158889229</FACEBOOK></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('facebook');
				}
			],
			[
				'https://www.facebook.com/FacebookDevelopers/posts/10151471074398553',
				'<r><FACEBOOK id="10151471074398553" type="p" user="FacebookDevelopers">https://www.facebook.com/FacebookDevelopers/posts/10151471074398553</FACEBOOK></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('facebook');
				}
			],
			[
				'https://de-de.facebook.com/FacebookDevelopers/posts/10151471074398553',
				'<r><FACEBOOK id="10151471074398553" type="p" user="FacebookDevelopers">https://de-de.facebook.com/FacebookDevelopers/posts/10151471074398553</FACEBOOK></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('facebook');
				}
			],
			[
				'https://www.facebook.com/photo.php?fbid=10152476416772631',
				'<r><FACEBOOK id="10152476416772631">https://www.facebook.com/photo.php?fbid=10152476416772631</FACEBOOK></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('facebook');
				}
			],
			[
				'https://www.facebook.com/pages/Bourne-Ultimatum/105742379466221',
				'<t>https://www.facebook.com/pages/Bourne-Ultimatum/105742379466221</t>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('facebook');
				}
			],
			[
				'https://apps.facebook.com/concertsbybit/facebook/events/8362556/rsvp',
				'<t>https://apps.facebook.com/concertsbybit/facebook/events/8362556/rsvp</t>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('facebook');
				}
			],
			[
				'https://www.facebook.com/events/640436826054815/',
				'<r><FACEBOOK id="640436826054815">https://www.facebook.com/events/640436826054815/</FACEBOOK></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('facebook');
				}
			],
			[
				'https://www.facebook.com/groups/257086497821359/',
				'<t>https://www.facebook.com/groups/257086497821359/</t>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('facebook');
				}
			],
			[
				'https://www.facebook.com/groups/257086497821359/permalink/262329290630413/',
				'<r><FACEBOOK id="262329290630413">https://www.facebook.com/groups/257086497821359/permalink/262329290630413/</FACEBOOK></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('facebook');
				}
			],
			[
				'https://www.facebook.com/permalink.php?story_fbid=10152253595081467&id=58617016466',
				'<r><FACEBOOK id="10152253595081467">https://www.facebook.com/permalink.php?story_fbid=10152253595081467&amp;id=58617016466</FACEBOOK></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('facebook');
				}
			],
			[
				'https://www.facebook.com/events/787127511306384/permalink/849632838389184/',
				'<r><FACEBOOK id="849632838389184">https://www.facebook.com/events/787127511306384/permalink/849632838389184/</FACEBOOK></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('facebook');
				}
			],
			[
				'https://web.facebook.com/VijayTelevision/videos/948642131881684/',
				'<r><FACEBOOK id="948642131881684" type="v" user="VijayTelevision">https://web.facebook.com/VijayTelevision/videos/948642131881684/</FACEBOOK></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('facebook');
				}
			],
			[
				'https://www.facebook.com/www.JOE.co.uk/videos/2478883195723649/',
				'<r><FACEBOOK id="2478883195723649" type="v" user="www.JOE.co.uk">https://www.facebook.com/www.JOE.co.uk/videos/2478883195723649/</FACEBOOK></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('facebook');
				}
			],
			[
				'https://www.facebook.com/marketplace/item/500023920905237/',
				'<t>https://www.facebook.com/marketplace/item/500023920905237/</t>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('facebook');
				}
			],
			[
				'https://www.facebook.com/groups/cardiffcityforum/posts/891173545141933/',
				'<r><FACEBOOK id="891173545141933" type="p">https://www.facebook.com/groups/cardiffcityforum/posts/891173545141933/</FACEBOOK></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('facebook');
				}
			],
			[
				'https://www.falstad.com/circuit/circuitjs.html?cct=$+1+0.000005+1.3241202019156522+43+5+50%0AR+736+368+736+304+0+0+40+3.3+0+0+0.5%0A162+736+368+736+416+2+default-led+1+0+0+0.01%0At+688+432+736+432+0+1+-0.2745603107888651+0.7370392451696097+100%0At+688+464+736+464+0+1+0.548427436901697+0.7373314588213099+100%0As+672+416+672+432+0+0+false%0As+672+448+672+464+0+0+false%0Aw+672+448+656+448+0%0Aw+672+416+656+416+0%0Aw+656+416+656+368+0%0Aw+656+368+736+368+0%0Aw+656+448+624+448+0%0Aw+624+448+624+368+0%0Aw+624+368+656+368+0%0Aw+736+480+736+528+0%0Ag+736+528+736+560+0%0Ar+688+432+672+432+0+1000%0Ar+688+464+672+464+0+1000%0Aw+736+416+800+416+0%0A162+800+416+880+416+2+default-led+1+0+0+0.01%0Ag+880+416+912+416+0%0A',
				'<r><FALSTAD cct="$+1+0.000005+1.3241202019156522+43+5+50%0AR+736+368+736+304+0+0+40+3.3+0+0+0.5%0A162+736+368+736+416+2+default-led+1+0+0+0.01%0At+688+432+736+432+0+1+-0.2745603107888651+0.7370392451696097+100%0At+688+464+736+464+0+1+0.548427436901697+0.7373314588213099+100%0As+672+416+672+432+0+0+false%0As+672+448+672+464+0+0+false%0Aw+672+448+656+448+0%0Aw+672+416+656+416+0%0Aw+656+416+656+368+0%0Aw+656+368+736+368+0%0Aw+656+448+624+448+0%0Aw+624+448+624+368+0%0Aw+624+368+656+368+0%0Aw+736+480+736+528+0%0Ag+736+528+736+560+0%0Ar+688+432+672+432+0+1000%0Ar+688+464+672+464+0+1000%0Aw+736+416+800+416+0%0A162+800+416+880+416+2+default-led+1+0+0+0.01%0Ag+880+416+912+416+0%0A">https://www.falstad.com/circuit/circuitjs.html?cct=$+1+0.000005+1.3241202019156522+43+5+50%0AR+736+368+736+304+0+0+40+3.3+0+0+0.5%0A162+736+368+736+416+2+default-led+1+0+0+0.01%0At+688+432+736+432+0+1+-0.2745603107888651+0.7370392451696097+100%0At+688+464+736+464+0+1+0.548427436901697+0.7373314588213099+100%0As+672+416+672+432+0+0+false%0As+672+448+672+464+0+0+false%0Aw+672+448+656+448+0%0Aw+672+416+656+416+0%0Aw+656+416+656+368+0%0Aw+656+368+736+368+0%0Aw+656+448+624+448+0%0Aw+624+448+624+368+0%0Aw+624+368+656+368+0%0Aw+736+480+736+528+0%0Ag+736+528+736+560+0%0Ar+688+432+672+432+0+1000%0Ar+688+464+672+464+0+1000%0Aw+736+416+800+416+0%0A162+800+416+880+416+2+default-led+1+0+0+0.01%0Ag+880+416+912+416+0%0A</FALSTAD></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('falstad');
				}
			],
			[
				'https://www.falstad.com/circuit/circuitjs.html?ctz=CQAgjCAMB0l3BWK0BsCBMkAcYCcZMBmQyMAkBSCiyAKAHdxyAWLETN1qB97EL9Fk5s6jQcJCEszfiNoAnJullLwAdhRRJJBbwkd1mqmEiZd4lRbAatJs4qkyujw1sJnGL65pfpmVOgAXdiF2BB9pMKNwEABaQmgsSjVcNXQUXEhcZjBmGRhw6zAEQjUcUyxcQXA4HgNvELZ0cO4xUIaDZqNaYM6WsmUu22QUQlwUcXTBSlxcEVQsKTGwKRKliBNRVQaB9n9uACVVLhQnES19pADkBFoAc1V0NNUSfPvJSKflF1fuAA9wAhcOxyNYmsV+OBNABhMC0AGEawgwjaTToMCaGQYkDQ9DwkBJdgoYyQJDpFFYzQARThCMyYWBOSoU0h2KpeP8KLwgz46D8e2uJgA+l0hZAhUz0CLoAgxdAwGKRULhYRaJz2M8DL8-NdcCLworJdLZTBmIqpXrVZhgbsOnwGgFYPAwGxYgVrJRKGgkjkUNi4daPlibFYbI74KQ2Ak0lgUGpCJRY6NMJo4QB7JggU5Z-hZYFYRL+XAlAiYZhzMrIZ1aZRsFC0IA',
				'<r><FALSTAD ctz="CQAgjCAMB0l3BWK0BsCBMkAcYCcZMBmQyMAkBSCiyAKAHdxyAWLETN1qB97EL9Fk5s6jQcJCEszfiNoAnJullLwAdhRRJJBbwkd1mqmEiZd4lRbAatJs4qkyujw1sJnGL65pfpmVOgAXdiF2BB9pMKNwEABaQmgsSjVcNXQUXEhcZjBmGRhw6zAEQjUcUyxcQXA4HgNvELZ0cO4xUIaDZqNaYM6WsmUu22QUQlwUcXTBSlxcEVQsKTGwKRKliBNRVQaB9n9uACVVLhQnES19pADkBFoAc1V0NNUSfPvJSKflF1fuAA9wAhcOxyNYmsV+OBNABhMC0AGEawgwjaTToMCaGQYkDQ9DwkBJdgoYyQJDpFFYzQARThCMyYWBOSoU0h2KpeP8KLwgz46D8e2uJgA+l0hZAhUz0CLoAgxdAwGKRULhYRaJz2M8DL8-NdcCLworJdLZTBmIqpXrVZhgbsOnwGgFYPAwGxYgVrJRKGgkjkUNi4daPlibFYbI74KQ2Ak0lgUGpCJRY6NMJo4QB7JggU5Z-hZYFYRL+XAlAiYZhzMrIZ1aZRsFC0IA">https://www.falstad.com/circuit/circuitjs.html?ctz=CQAgjCAMB0l3BWK0BsCBMkAcYCcZMBmQyMAkBSCiyAKAHdxyAWLETN1qB97EL9Fk5s6jQcJCEszfiNoAnJullLwAdhRRJJBbwkd1mqmEiZd4lRbAatJs4qkyujw1sJnGL65pfpmVOgAXdiF2BB9pMKNwEABaQmgsSjVcNXQUXEhcZjBmGRhw6zAEQjUcUyxcQXA4HgNvELZ0cO4xUIaDZqNaYM6WsmUu22QUQlwUcXTBSlxcEVQsKTGwKRKliBNRVQaB9n9uACVVLhQnES19pADkBFoAc1V0NNUSfPvJSKflF1fuAA9wAhcOxyNYmsV+OBNABhMC0AGEawgwjaTToMCaGQYkDQ9DwkBJdgoYyQJDpFFYzQARThCMyYWBOSoU0h2KpeP8KLwgz46D8e2uJgA+l0hZAhUz0CLoAgxdAwGKRULhYRaJz2M8DL8-NdcCLworJdLZTBmIqpXrVZhgbsOnwGgFYPAwGxYgVrJRKGgkjkUNi4daPlibFYbI74KQ2Ak0lgUGpCJRY6NMJo4QB7JggU5Z-hZYFYRL+XAlAiYZhzMrIZ1aZRsFC0IA</FALSTAD></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('falstad');
				}
			],
			[
				'https://www.flickr.com/photos/erigion/15451038758/in/photostream/',
				'<r><FLICKR id="15451038758">https://www.flickr.com/photos/erigion/15451038758/in/photostream/</FLICKR></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('flickr');
				}
			],
			[
				'https://flic.kr/8757881@N04/2971804544',
				'<r><FLICKR id="2971804544">https://flic.kr/8757881@N04/2971804544</FLICKR></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('flickr');
				}
			],
			[
				'http://video.foxnews.com/v/3592758613001/reddit-helps-fund-homemade-hot-sauce-venture/',
				'<r><FOXNEWS id="3592758613001">http://video.foxnews.com/v/3592758613001/reddit-helps-fund-homemade-hot-sauce-venture/</FOXNEWS></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('foxnews');
				}
			],
			[
				'http://www.funnyordie.com/videos/bf313bd8b4/murdock-with-keith-david',
				'<r><FUNNYORDIE id="bf313bd8b4">http://www.funnyordie.com/videos/bf313bd8b4/murdock-with-keith-david</FUNNYORDIE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('funnyordie');
				}
			],
			[
				'http://www.gamespot.com/destiny/videos/destiny-the-moon-trailer-6415176/',
				'<r><GAMESPOT id="6415176">http://www.gamespot.com/destiny/videos/destiny-the-moon-trailer-6415176/</GAMESPOT></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('gamespot');
				}
			],
			[
				'http://www.gamespot.com/events/game-crib-tsm-snapdragon/gamecrib-extras-cooking-with-dan-dinh-6412922/',
				'<r><GAMESPOT id="6412922">http://www.gamespot.com/events/game-crib-tsm-snapdragon/gamecrib-extras-cooking-with-dan-dinh-6412922/</GAMESPOT></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('gamespot');
				}
			],
			[
				'http://www.gamespot.com/videos/beat-the-pros-pax-prime-2013/2300-6414307/',
				'<r><GAMESPOT id="6414307">http://www.gamespot.com/videos/beat-the-pros-pax-prime-2013/2300-6414307/</GAMESPOT></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('gamespot');
				}
			],
			[
				'https://gist.github.com/s9e/6806305',
				'<r><GIST id="s9e/6806305">https://gist.github.com/s9e/6806305</GIST></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('gist');
				}
			],
			[
				'https://gist.github.com/6806305',
				'<r><GIST id="6806305">https://gist.github.com/6806305</GIST></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('gist');
				}
			],
			[
				'https://gist.github.com/s9e/6806305/ad88d904b082c8211afa040162402015aacb8599',
				'<r><GIST id="s9e/6806305/ad88d904b082c8211afa040162402015aacb8599">https://gist.github.com/s9e/6806305/ad88d904b082c8211afa040162402015aacb8599</GIST></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('gist');
				}
			],
			[
				'https://gist.github.com/s9e/0ee8433f5a9a779d08ef',
				'<r><GIST id="s9e/0ee8433f5a9a779d08ef">https://gist.github.com/s9e/0ee8433f5a9a779d08ef</GIST></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('gist');
				}
			],
			[
				'https://gist.github.com/Untrusted-Game/172a510a34cf0ba9cf7533dc9f55d960/ffe65e387a97ee479cbeaa82660f5af42aa80cb1',
				'<r><GIST id="Untrusted-Game/172a510a34cf0ba9cf7533dc9f55d960/ffe65e387a97ee479cbeaa82660f5af42aa80cb1">https://gist.github.com/Untrusted-Game/172a510a34cf0ba9cf7533dc9f55d960/ffe65e387a97ee479cbeaa82660f5af42aa80cb1</GIST></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('gist');
				}
			],
			[
				'https://gist.github.com/Untrusted-Game/172a510a34cf0ba9cf7533dc9f55d960/archive/ffe65e387a97ee479cbeaa82660f5af42aa80cb1.zip',
				'<t>https://gist.github.com/Untrusted-Game/172a510a34cf0ba9cf7533dc9f55d960/archive/ffe65e387a97ee479cbeaa82660f5af42aa80cb1.zip</t>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('gist');
				}
			],
			[
				'http://globalnews.ca/video/1647385/mark-channels-his-70s-look/',
				'<r><GLOBALNEWS id="1647385">http://globalnews.ca/video/1647385/mark-channels-his-70s-look/</GLOBALNEWS></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('globalnews');
				}
			],
			[
				'http://www.gofundme.com/2p37ao',
				'<r><GOFUNDME id="2p37ao">http://www.gofundme.com/2p37ao</GOFUNDME></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('gofundme');
				}
			],
			[
				'http://www.gofundme.com/2p37ao#',
				'<r><GOFUNDME id="2p37ao">http://www.gofundme.com/2p37ao#</GOFUNDME></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('gofundme');
				}
			],
			[
				'http://www.gofundme.com/2p37ao?pc=trend',
				'<r><GOFUNDME id="2p37ao">http://www.gofundme.com/2p37ao?pc=trend</GOFUNDME></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('gofundme');
				}
			],
			[
				'http://www.gofundme.com/tour/',
				'<t>http://www.gofundme.com/tour/</t>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('gofundme');
				}
			],
			[
				'https://drive.google.com/file/d/0B_4NRUjxLBejNjVmeG5MUzA3Q3M/view?usp=sharing',
				'<r><GOOGLEDRIVE id="0B_4NRUjxLBejNjVmeG5MUzA3Q3M">https://drive.google.com/file/d/0B_4NRUjxLBejNjVmeG5MUzA3Q3M/view?usp=sharing</GOOGLEDRIVE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('googledrive');
				}
			],
			[
				// https://github.com/s9e/phpbb-ext-mediaembed/issues/9
				'https://drive.google.com/open?id=1TAnofDHLM-Mreaju0l3--9SQAESEIpD9AA',
				'<r><GOOGLEDRIVE id="1TAnofDHLM-Mreaju0l3--9SQAESEIpD9AA">https://drive.google.com/open?id=1TAnofDHLM-Mreaju0l3--9SQAESEIpD9AA</GOOGLEDRIVE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('googledrive');
				}
			],
			[
				'https://drive.google.com/a/monashores.net/file/d/0B-SjC2QWxqXRY2NfbGJ2QUcwTlU/view',
				'<r><GOOGLEDRIVE id="0B-SjC2QWxqXRY2NfbGJ2QUcwTlU">https://drive.google.com/a/monashores.net/file/d/0B-SjC2QWxqXRY2NfbGJ2QUcwTlU/view</GOOGLEDRIVE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('googledrive');
				}
			],
			[
				'https://plus.google.com/110286587261352351537/posts/XMABm8rLvRW',
				'<r><GOOGLEPLUS oid="110286587261352351537" pid="XMABm8rLvRW">https://plus.google.com/110286587261352351537/posts/XMABm8rLvRW</GOOGLEPLUS></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('googleplus');
				}
			],
			[
				'https://plus.google.com/+JacekMiłaszewski/posts/HJEFk3SX1sL',
				'<r><GOOGLEPLUS name="JacekMiłaszewski" pid="HJEFk3SX1sL">https://plus.google.com/+JacekMiłaszewski/posts/HJEFk3SX1sL</GOOGLEPLUS></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('googleplus');
				}
			],
			[
				'https://plus.google.com/+JacekMi%C5%82aszewski/posts/HJEFk3SX1sL',
				'<r><GOOGLEPLUS name="JacekMiłaszewski" pid="HJEFk3SX1sL">https://plus.google.com/+JacekMi%C5%82aszewski/posts/HJEFk3SX1sL</GOOGLEPLUS></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('googleplus');
				}
			],
			[
				'https://docs.google.com/spreadsheets/d/1e-WiRxaToQyKPkm1x8hRu6cN6K0aQFxExo7RnCymxGE',
				'<r><GOOGLESHEETS id="1e-WiRxaToQyKPkm1x8hRu6cN6K0aQFxExo7RnCymxGE">https://docs.google.com/spreadsheets/d/1e-WiRxaToQyKPkm1x8hRu6cN6K0aQFxExo7RnCymxGE</GOOGLESHEETS></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('googlesheets');
				}
			],
			[
				'https://docs.google.com/spreadsheet/ccc?key=0AnfAFqEAnlFvdG5IMDdnd0xZQUlxZkdxbzg5SGZJQlE&usp=sharing',
				'<r><GOOGLESHEETS id="0AnfAFqEAnlFvdG5IMDdnd0xZQUlxZkdxbzg5SGZJQlE">https://docs.google.com/spreadsheet/ccc?key=0AnfAFqEAnlFvdG5IMDdnd0xZQUlxZkdxbzg5SGZJQlE&amp;usp=sharing</GOOGLESHEETS></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('googlesheets');
				}
			],
			[
				'https://docs.google.com/spreadsheet/ccc?key=0An1aCHqyU7FqdGtBUDc1S1NNSWhqY3NidndIa1JuQWc#gid=70',
				'<r><GOOGLESHEETS gid="70" id="0An1aCHqyU7FqdGtBUDc1S1NNSWhqY3NidndIa1JuQWc">https://docs.google.com/spreadsheet/ccc?key=0An1aCHqyU7FqdGtBUDc1S1NNSWhqY3NidndIa1JuQWc#gid=70</GOOGLESHEETS></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('googlesheets');
				}
			],
			[
				'https://docs.google.com/spreadsheets/u/1/d/e/2PACX-1vQ7iYECa_ziPWygKBf9Og-PkQl5-gdnFRU-jP-z1FyjLfLXMAdRJN1J5Lx31NjnCq5lkRcL-T0Idh4_/pubhtml',
				'<r><GOOGLESHEETS id="e/2PACX-1vQ7iYECa_ziPWygKBf9Og-PkQl5-gdnFRU-jP-z1FyjLfLXMAdRJN1J5Lx31NjnCq5lkRcL-T0Idh4_">https://docs.google.com/spreadsheets/u/1/d/e/2PACX-1vQ7iYECa_ziPWygKBf9Og-PkQl5-gdnFRU-jP-z1FyjLfLXMAdRJN1J5Lx31NjnCq5lkRcL-T0Idh4_/pubhtml</GOOGLESHEETS></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('googlesheets');
				}
			],
			[
				'http://www.hudl.com/athlete/2067184/highlights/163744377',
				'<r><HUDL athlete="2067184" highlight="163744377">http://www.hudl.com/athlete/2067184/highlights/163744377</HUDL></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('hudl');
				}
			],
			[
				'http://www.hudl.com/video/3/323679/57719969842eb243e47883f8',
				'<r><HUDL athlete="323679" highlight="57719969842eb243e47883f8">http://www.hudl.com/video/3/323679/57719969842eb243e47883f8</HUDL></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('hudl');
				}
			],
			[
				'https://www.hudl.com/video/5bcf7096f56a8b0e1c93e439',
				'<r><HUDL id="5bcf7096f56a8b0e1c93e439">https://www.hudl.com/video/5bcf7096f56a8b0e1c93e439</HUDL></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('hudl');
				}
			],
			[
				'http://uk.ign.com/videos/2013/07/12/pokemon-x-version-pokemon-y-version-battle-trailer',
				'<r><IGN id="http://uk.ign.com/videos/2013/07/12/pokemon-x-version-pokemon-y-version-battle-trailer">http://uk.ign.com/videos/2013/07/12/pokemon-x-version-pokemon-y-version-battle-trailer</IGN></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('ign');
				}
			],
			[
				'Http://uk.ign.com/videos/2013/07/12/pokemon-x-version-pokemon-y-version-battle-trailer',
				'<r><IGN id="Http://uk.ign.com/videos/2013/07/12/pokemon-x-version-pokemon-y-version-battle-trailer">Http://uk.ign.com/videos/2013/07/12/pokemon-x-version-pokemon-y-version-battle-trailer</IGN></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('ign');
				}
			],
			[
				'http://i.imgur.com/AsQ0K3P.jpg',
				'<r><IMGUR id="AsQ0K3P">http://i.imgur.com/AsQ0K3P.jpg</IMGUR></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('imgur');
				}
			],
			[
				'https://i.imgur.com/aPAyaEs.jpg',
				'<r><IMGUR id="aPAyaEs">https://i.imgur.com/aPAyaEs.jpg</IMGUR></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('imgur');
				}
			],
			[
				// Thumbnails have the same URL as the full image with an extra "l", "m" or "s"
				'https://i.imgur.com/aPAyaEss.jpg',
				'<r><IMGUR id="aPAyaEs">https://i.imgur.com/aPAyaEss.jpg</IMGUR></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('imgur');
				}
			],
			[
				'http://imgur.com/r/animals',
				'<t>http://imgur.com/r/animals</t>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('imgur');
				}
			],
			[
				'http://imgur.com/user/foo',
				'<t>http://imgur.com/user/foo</t>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('imgur');
				}
			],
			[
				'https://imgur.com/a/9UGCL',
				'<r><IMGUR id="a/9UGCL">https://imgur.com/a/9UGCL</IMGUR></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('imgur');
				}
			],
			[
				'http://www.indiegogo.com/projects/513633',
				'<r><INDIEGOGO id="513633">http://www.indiegogo.com/projects/513633</INDIEGOGO></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('indiegogo');
				}
			],
			[
				'http://www.indiegogo.com/projects/gameheart-redesigned',
				'<r><INDIEGOGO id="gameheart-redesigned">http://www.indiegogo.com/projects/gameheart-redesigned</INDIEGOGO></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('indiegogo');
				}
			],
			[
				'http://www.indiegogo.com/projects/5050-years-a-documentary',
				'<r><INDIEGOGO id="5050-years-a-documentary">http://www.indiegogo.com/projects/5050-years-a-documentary</INDIEGOGO></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('indiegogo');
				}
			],
			[
				'http://instagram.com/p/gbGaIXBQbn/',
				'<r><INSTAGRAM id="gbGaIXBQbn">http://instagram.com/p/gbGaIXBQbn/</INSTAGRAM></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('instagram');
				}
			],
			[
				'http://instagram.com/p/lx39ciHzD_/',
				'<r><INSTAGRAM id="lx39ciHzD_">http://instagram.com/p/lx39ciHzD_/</INSTAGRAM></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('instagram');
				}
			],
			[
				'http://instagram.com/p/k28LE0Dte-/',
				'<r><INSTAGRAM id="k28LE0Dte-">http://instagram.com/p/k28LE0Dte-/</INSTAGRAM></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('instagram');
				}
			],
			[
				'https://www.instagram.com/tv/BkQjCfsBIzi/',
				'<r><INSTAGRAM id="BkQjCfsBIzi">https://www.instagram.com/tv/BkQjCfsBIzi/</INSTAGRAM></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('instagram');
				}
			],
			[
				'https://www.instagram.com/reel/CJZFoN6pHth/',
				'<r><INSTAGRAM id="CJZFoN6pHth">https://www.instagram.com/reel/CJZFoN6pHth/</INSTAGRAM></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('instagram');
				}
			],
			[
				'http://www.izlesene.com/video/lily-allen-url-badman/7600704',
				'<r><IZLESENE id="7600704">http://www.izlesene.com/video/lily-allen-url-badman/7600704</IZLESENE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('izlesene');
				}
			],
			[
				'https://jsfiddle.net/1v03Lqnu/',
				'<r><JSFIDDLE id="1v03Lqnu">https://jsfiddle.net/1v03Lqnu/</JSFIDDLE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('jsfiddle');
				}
			],
			[
				'https://jsfiddle.net/1v03Lqnu/11/',
				'<r><JSFIDDLE id="1v03Lqnu" revision="11">https://jsfiddle.net/1v03Lqnu/11/</JSFIDDLE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('jsfiddle');
				}
			],
			[
				'https://jsfiddle.net/nicodak/6jucLkex/',
				'<r><JSFIDDLE id="6jucLkex" user="nicodak">https://jsfiddle.net/nicodak/6jucLkex/</JSFIDDLE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('jsfiddle');
				}
			],
			[
				'https://jsfiddle.net/nicodak/6jucLkex/5/',
				'<r><JSFIDDLE id="6jucLkex" revision="5" user="nicodak">https://jsfiddle.net/nicodak/6jucLkex/5/</JSFIDDLE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('jsfiddle');
				}
			],
			[
				'https://jsfiddle.net/1v03Lqnu/show',
				'<r><JSFIDDLE id="1v03Lqnu">https://jsfiddle.net/1v03Lqnu/show</JSFIDDLE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('jsfiddle');
				}
			],
			[
				'http://content.jwplatform.com/players/X6tRZpKj-7Y21S9TB.html',
				'<r><JWPLATFORM id="X6tRZpKj-7Y21S9TB">http://content.jwplatform.com/players/X6tRZpKj-7Y21S9TB.html</JWPLATFORM></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('jwplatform');
				}
			],
			[
				'http://content.jwplatform.com/previews/8YYjnBKd-plsZnDJi',
				'<r><JWPLATFORM id="8YYjnBKd-plsZnDJi">http://content.jwplatform.com/previews/8YYjnBKd-plsZnDJi</JWPLATFORM></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('jwplatform');
				}
			],
			[
				'http://content.jwplatform.com/videos/5W1gTgdo-wevz73lD.mp4',
				'<r><JWPLATFORM id="5W1gTgdo-wevz73lD">http://content.jwplatform.com/videos/5W1gTgdo-wevz73lD.mp4</JWPLATFORM></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('jwplatform');
				}
			],
			[
				'https://www.kaltura.com/index.php/extwidget/preview/partner_id/1581781/uiconf_id/20490561/entry_id/0_ekjebl7h/embed/iframe',
				'<r><KALTURA entry_id="0_ekjebl7h" partner_id="1581781" uiconf_id="20490561">https://www.kaltura.com/index.php/extwidget/preview/partner_id/1581781/uiconf_id/20490561/entry_id/0_ekjebl7h/embed/iframe</KALTURA></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('kaltura');
				}
			],
			[
				'http://www.kickstarter.com/projects/1869987317/wish-i-was-here-1?ref=',
				'<r><KICKSTARTER id="1869987317/wish-i-was-here-1">http://www.kickstarter.com/projects/1869987317/wish-i-was-here-1?ref=</KICKSTARTER></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('kickstarter');
				}
			],
			[
				'http://www.kickstarter.com/projects/1869987317/wish-i-was-here-1/widget/card.html',
				'<r><KICKSTARTER card="card" id="1869987317/wish-i-was-here-1">http://www.kickstarter.com/projects/1869987317/wish-i-was-here-1/widget/card.html</KICKSTARTER></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('kickstarter');
				}
			],
			[
				'http://www.kickstarter.com/projects/1869987317/wish-i-was-here-1/widget/video.html',
				'<r><KICKSTARTER id="1869987317/wish-i-was-here-1" video="video">http://www.kickstarter.com/projects/1869987317/wish-i-was-here-1/widget/video.html</KICKSTARTER></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('kickstarter');
				}
			],
//			[
//				'https://www.liveleak.com/view?i=Clka1_1520190526',
//				'<r><LIVELEAK id="Clka1_1520190526">https://www.liveleak.com/view?i=Clka1_1520190526</LIVELEAK></r>',
//				[],
//				function ($configurator)
//				{
//					$configurator->MediaEmbed->add('liveleak');
//				}
//			],
			[
				'http://new.livestream.com/accounts/9999999999/events/9999999999',
				'<r><LIVESTREAM account_id="9999999999" event_id="9999999999">http://new.livestream.com/accounts/9999999999/events/9999999999</LIVESTREAM></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('livestream');
				}
			],
			[
				'https://medium.com/@donnydonny/team-internet-is-about-to-win-net-neutrality-and-they-didnt-need-googles-help-e7e2cf9b8a95',
				'<r><MEDIUM id="e7e2cf9b8a95">https://medium.com/@donnydonny/team-internet-is-about-to-win-net-neutrality-and-they-didnt-need-googles-help-e7e2cf9b8a95</MEDIUM></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('medium');
				}
			],
			[
				'https://medium.com/s/story/how-to-make-your-phone-into-an-ally-not-an-enemy-5e0f4302df6c',
				'<r><MEDIUM id="5e0f4302df6c">https://medium.com/s/story/how-to-make-your-phone-into-an-ally-not-an-enemy-5e0f4302df6c</MEDIUM></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('medium');
				}
			],
			[
				'https://elemental.medium.com/your-surge-capacity-is-depleted-it-s-why-you-feel-awful-de285d542f4c',
				'<r><MEDIUM id="de285d542f4c">https://elemental.medium.com/your-surge-capacity-is-depleted-it-s-why-you-feel-awful-de285d542f4c</MEDIUM></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('medium');
				}
			],
			[
				'https://medium.com/p/de285d542f4c',
				'<r><MEDIUM id="de285d542f4c">https://medium.com/p/de285d542f4c</MEDIUM></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('medium');
				}
			],
			[
				'https://medium.com/feedium/100-popular-on-medium-stories-here-s-what-i-found-3702329e5093',
				'<r><MEDIUM id="3702329e5093">https://medium.com/feedium/100-popular-on-medium-stories-here-s-what-i-found-3702329e5093</MEDIUM></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('medium');
				}
			],
			[
				'https://medium.com/an-idea/defunding-is-great-but-lets-talk-about-disarming-the-police-177cdbf3d9af',
				'<r><MEDIUM id="177cdbf3d9af">https://medium.com/an-idea/defunding-is-great-but-lets-talk-about-disarming-the-police-177cdbf3d9af</MEDIUM></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('medium');
				}
			],
			[
				'https://cms.megaphone.fm/channel/lockedonheat?selected=LKN8165322853',
				'<r><MEGAPHONE id="LKN8165322853">https://cms.megaphone.fm/channel/lockedonheat?selected=LKN8165322853</MEGAPHONE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('megaphone');
				}
			],
			[
				'https://player.megaphone.fm/LKN8165322853',
				'<r><MEGAPHONE id="LKN8165322853">https://player.megaphone.fm/LKN8165322853</MEGAPHONE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('megaphone');
				}
			],
			[
				'https://playlist.megaphone.fm/?e=BL3815124285',
				'<r><MEGAPHONE id="BL3815124285">https://playlist.megaphone.fm/?e=BL3815124285</MEGAPHONE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('megaphone');
				}
			],
			[
				'https://traffic.megaphone.fm/BWG2907286286.mp3',
				'<r><MEGAPHONE id="BWG2907286286">https://traffic.megaphone.fm/BWG2907286286.mp3</MEGAPHONE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('megaphone');
				}
			],
			[
				'https://megaphone.link/STU8778452176',
				'<r><MEGAPHONE id="STU8778452176">https://megaphone.link/STU8778452176</MEGAPHONE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('megaphone');
				}
			],
			[
				'https://dcs.megaphone.fm/HSW1449223469.mp3?key=e9f77ccda70e1295a7d5ec683ace532d',
				'<r><MEGAPHONE id="HSW1449223469">https://dcs.megaphone.fm/HSW1449223469.mp3?key=e9f77ccda70e1295a7d5ec683ace532d</MEGAPHONE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('megaphone');
				}
			],
			[
				'http://www.metacafe.com/watch/10785282/chocolate_treasure_chest_epic_meal_time/',
				'<r><METACAFE id="10785282">http://www.metacafe.com/watch/10785282/chocolate_treasure_chest_epic_meal_time/</METACAFE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('metacafe');
				}
			],
			[
				'http://www.mixcloud.com/OneTakeTapes/timsch-one-take-tapes-2/',
				'<r><MIXCLOUD id="OneTakeTapes/timsch-one-take-tapes-2">http://www.mixcloud.com/OneTakeTapes/timsch-one-take-tapes-2/</MIXCLOUD></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('mixcloud');
				}
			],
			[
				'https://www.mixcloud.com/s2ck/dj-miiiiiit-mustern-drauf-guestmix-f%C3%BCr-kil-seine-party-liebe-gr%C3%BC%C3%9Fe-aus-freiburg/',
				'<r><MIXCLOUD id="s2ck/dj-miiiiiit-mustern-drauf-guestmix-f%C3%BCr-kil-seine-party-liebe-gr%C3%BC%C3%9Fe-aus-freiburg">https://www.mixcloud.com/s2ck/dj-miiiiiit-mustern-drauf-guestmix-f%C3%BCr-kil-seine-party-liebe-gr%C3%BC%C3%9Fe-aus-freiburg/</MIXCLOUD></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('mixcloud');
				}
			],
			[
				'https://www.mixcloud.com/s2ck/dj-miiiiiit-mustern-drauf-guestmix-für-kil-seine-party-liebe-grüße-aus-freiburg/',
				'<r><MIXCLOUD id="s2ck/dj-miiiiiit-mustern-drauf-guestmix-für-kil-seine-party-liebe-grüße-aus-freiburg">https://www.mixcloud.com/s2ck/dj-miiiiiit-mustern-drauf-guestmix-für-kil-seine-party-liebe-grüße-aus-freiburg/</MIXCLOUD></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('mixcloud');
				}
			],
			[
				'http://www.mixcloud.com/OneTakeTapes/timsch-one-take-tapes-2&foo=1/',
				'<t>http://www.mixcloud.com/OneTakeTapes/timsch-one-take-tapes-2&amp;foo=1/</t>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('mixcloud');
				}
			],
			[
				'http://www.mixcloud.com/categories/classical/',
				'<t>http://www.mixcloud.com/categories/classical/</t>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('mixcloud');
				}
			],
			[
				'http://www.mixcloud.com/tag/npr/',
				'<t>http://www.mixcloud.com/tag/npr/</t>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('mixcloud');
				}
			],
			[
				'http://m.mlb.com/video/v1205791883/hughes-and-coomer-call-baezs-seriesclinching-hit',
				'<r><MLB id="1205791883">http://m.mlb.com/video/v1205791883/hughes-and-coomer-call-baezs-seriesclinching-hit</MLB></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('mlb');
				}
			],
			[
				'https://www.mlb.com/video/statcast-stantons-two-homers/c-1898424783',
				'<r><MLB id="1898424783">https://www.mlb.com/video/statcast-stantons-two-homers/c-1898424783</MLB></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('mlb');
				}
			],
			[
				'https://www.mlb.com/video/harper-s-1st-game-with-phillies-c2523480183',
				'<r><MLB id="2523480183">https://www.mlb.com/video/harper-s-1st-game-with-phillies-c2523480183</MLB></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('mlb');
				}
			],
			[
				'https://www.mlb.com/news/yankees-mckinney-exits-game/c-270278462',
				'<t>https://www.mlb.com/news/yankees-mckinney-exits-game/c-270278462</t>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('mlb');
				}
			],
			[
				'http://channel.nationalgeographic.com/channel/brain-games/videos/jason-silva-on-intuition/',
				'<r><NATGEOCHANNEL id="channel/brain-games/videos/jason-silva-on-intuition">http://channel.nationalgeographic.com/channel/brain-games/videos/jason-silva-on-intuition/</NATGEOCHANNEL></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('natgeochannel');
					$configurator->MediaEmbed->add('natgeovideo');
				}
			],
			[
				'http://channel.nationalgeographic.com/wild/urban-jungle/videos/leopard-in-the-city/',
				'<r><NATGEOCHANNEL id="wild/urban-jungle/videos/leopard-in-the-city">http://channel.nationalgeographic.com/wild/urban-jungle/videos/leopard-in-the-city/</NATGEOCHANNEL></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('natgeochannel');
					$configurator->MediaEmbed->add('natgeovideo');
				}
			],
			[
				'http://www.nbcnews.com/video/bob-dylan-awarded-nobel-prize-for-literature-785193027834',
				'<r><NBCNEWS id="785193027834">http://www.nbcnews.com/video/bob-dylan-awarded-nobel-prize-for-literature-785193027834</NBCNEWS></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('nbcnews');
				}
			],
			[
				'http://www.nbcnews.com/widget/video-embed/785160259969',
				'<r><NBCNEWS id="785160259969">http://www.nbcnews.com/widget/video-embed/785160259969</NBCNEWS></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('nbcnews');
				}
			],
			[
				'https://www.nhl.com/video/korpikoski-scores-on-odd-man-rush/t-283069656/c-46322103',
				'<r><NHL c="46322103" t="283069656">https://www.nhl.com/video/korpikoski-scores-on-odd-man-rush/t-283069656/c-46322103</NHL></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('nhl');
				}
			],
			[
				'https://www.nhl.com/video/c-46299003',
				'<r><NHL c="46299003">https://www.nhl.com/video/c-46299003</NHL></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('nhl');
				}
			],
			[
				'https://www.nhl.com/video/t-281748732?partnerId=as_nhl_20161104_67553116&adbid=794558584411426816&adbpl=tw&adbpr=50004938',
				'<r><NHL t="281748732">https://www.nhl.com/video/t-281748732?partnerId=as_nhl_20161104_67553116&amp;adbid=794558584411426816&amp;adbpl=tw&amp;adbpr=50004938</NHL></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('nhl');
				}
			],
			[
				'https://www.nhl.com/canadiens/video/radulovs-odd-empty-net-goal/t-277443720/c-45954203',
				'<r><NHL c="45954203" t="277443720">https://www.nhl.com/canadiens/video/radulovs-odd-empty-net-goal/t-277443720/c-45954203</NHL></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('nhl');
				}
			],
			[
				'http://www.nytimes.com/video/technology/personaltech/100000002907606/soylent-taste-test.html',
				'<r><NYTIMES id="100000002907606">http://www.nytimes.com/video/technology/personaltech/100000002907606/soylent-taste-test.html</NYTIMES></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('nytimes');
				}
			],
			[
				'http://www.nytimes.com/video/2012/12/17/business/100000001950744/how-wal-mart-conquered-teotihuacan.html',
				'<r><NYTIMES id="100000001950744">http://www.nytimes.com/video/2012/12/17/business/100000001950744/how-wal-mart-conquered-teotihuacan.html</NYTIMES></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('nytimes');
				}
			],
			[
				'http://www.nytimes.com/video/magazine/100000003166834/small-plates.html',
				'<r><NYTIMES id="100000003166834">http://www.nytimes.com/video/magazine/100000003166834/small-plates.html</NYTIMES></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('nytimes');
				}
			],
			[
				'https://odysee.com/$/embed/the-secret-of-synchronization/21cdece6e3221864d9ea10d84f95a86cb8dad097',
				'<r><ODYSEE id="21cdece6e3221864d9ea10d84f95a86cb8dad097" name="the-secret-of-synchronization">https://odysee.com/$/embed/the-secret-of-synchronization/21cdece6e3221864d9ea10d84f95a86cb8dad097</ODYSEE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('odysee');
				}
			],
			[
				'https://odysee.com/the-secret-of-synchronization:21cdece6e3221864d9ea10d84f95a86cb8dad097',
				'<r><ODYSEE id="21cdece6e3221864d9ea10d84f95a86cb8dad097" name="the-secret-of-synchronization">https://odysee.com/the-secret-of-synchronization:21cdece6e3221864d9ea10d84f95a86cb8dad097</ODYSEE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('odysee');
				}
			],
			[
				'https://odysee.com/Deni-Juric-Goal-2-0-ŠIBENIK-vs-SLAVEN-Apr21:8726b01100463c4e254a38c3108ef3e05791aeda',
				'<r><ODYSEE id="8726b01100463c4e254a38c3108ef3e05791aeda" name="Deni-Juric-Goal-2-0-%C5%A0IBENIK-vs-SLAVEN-Apr21">https://odysee.com/Deni-Juric-Goal-2-0-ŠIBENIK-vs-SLAVEN-Apr21:8726b01100463c4e254a38c3108ef3e05791aeda</ODYSEE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('odysee');
				}
			],
			[
				'https://www.orfium.com/album/24371/everybody-loves-kanye-totom/',
				'<r><ORFIUM album_id="24371">https://www.orfium.com/album/24371/everybody-loves-kanye-totom/</ORFIUM></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('orfium');
				}
			],
			[
				'https://www.orfium.com/live-set/614763/foof-no-lights-5-foof/',
				'<r><ORFIUM set_id="614763">https://www.orfium.com/live-set/614763/foof-no-lights-5-foof/</ORFIUM></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('orfium');
				}
			],
			[
				'https://www.orfium.com/playlist/511651/electronic-live-sessions-creamtronic/',
				'<r><ORFIUM playlist_id="511651">https://www.orfium.com/playlist/511651/electronic-live-sessions-creamtronic/</ORFIUM></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('orfium');
				}
			],
			[
				'https://www.orfium.com/track/625367/the-ambience-of-the-goss-vistas/',
				'<r><ORFIUM track_id="625367">https://www.orfium.com/track/625367/the-ambience-of-the-goss-vistas/</ORFIUM></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('orfium');
				}
			],
			[
				'http://pastebin.com/9jEf44nc',
				'<r><PASTEBIN id="9jEf44nc">http://pastebin.com/9jEf44nc</PASTEBIN></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('pastebin');
				}
			],
			[
				'http://pastebin.com/u/username',
				'<t>http://pastebin.com/u/username</t>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('pastebin');
				}
			],
			[
				'http://pastebin.com/raw.php?i=9jEf44nc',
				'<r><PASTEBIN id="9jEf44nc">http://pastebin.com/raw.php?i=9jEf44nc</PASTEBIN></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('pastebin');
				}
			],
			[
				'http://pastebin.com/raw/9jEf44nc',
				'<r><PASTEBIN id="9jEf44nc">http://pastebin.com/raw/9jEf44nc</PASTEBIN></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('pastebin');
				}
			],
			[
				'https://www.pinterest.com/pin/99360735500167749/',
				'<r><PINTEREST id="99360735500167749">https://www.pinterest.com/pin/99360735500167749/</PINTEREST></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('pinterest');
				}
			],
			[
				'https://www.pinterest.com/pinterest/official-news/',
				'<r><PINTEREST id="pinterest/official-news">https://www.pinterest.com/pinterest/official-news/</PINTEREST></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('pinterest');
				}
			],
			[
				'https://www.pinterest.com/pin/create/button/?url=',
				'<t>https://www.pinterest.com/pin/create/button/?url=</t>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('pinterest');
				}
			],
			[
				'https://www.pinterest.com/explore/business-smart-dress-code/?lp=true',
				'<t>https://www.pinterest.com/explore/business-smart-dress-code/?lp=true</t>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('pinterest');
				}
			],
			[
				'http://www.podbean.com/media/share/pb-qtwub-4ee10c',
				'<r><PODBEAN id="qtwub-4ee10c">http://www.podbean.com/media/share/pb-qtwub-4ee10c</PODBEAN></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('podbean');
				}
			],
			[
				'https://www.podbean.com/ew/pb-gmyyz-d6e065',
				'<r><PODBEAN id="gmyyz-d6e065">https://www.podbean.com/ew/pb-gmyyz-d6e065</PODBEAN></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('podbean');
				}
			],
			[
				'http://prezi.com/5ye8po_hmikp/10-most-common-rookie-presentation-mistakes/',
				'<r><PREZI id="5ye8po_hmikp">http://prezi.com/5ye8po_hmikp/10-most-common-rookie-presentation-mistakes/</PREZI></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('prezi');
				}
			],
			[
				'http://blog.prezi.com/latest/2014/2/7/10-most-common-rookie-mistakes-in-public-speaking.html/',
				'<t>http://blog.prezi.com/latest/2014/2/7/10-most-common-rookie-mistakes-in-public-speaking.html/</t>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('prezi');
				}
			],
			[
				'http://prezi.com/explore/staff-picks/',
				'<t>http://prezi.com/explore/staff-picks/</t>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('prezi');
				}
			],
			[
				'http://www.reddit.com/r/pics/comments/304rms/cats_reaction_to_seeing_the_ceiling_fan_move_for',
				'<r><REDDIT id="pics/comments/304rms">http://www.reddit.com/r/pics/comments/304rms/cats_reaction_to_seeing_the_ceiling_fan_move_for</REDDIT></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('reddit');
				}
			],
			[
				'http://www.reddit.com/r/pics/comments/304rms/cats_reaction_to_seeing_the_ceiling_fan_move_for/cpp2kkl',
				'<r><REDDIT id="pics/comments/304rms/cats_reaction_to_seeing_the_ceiling_fan_move_for/cpp2kkl">http://www.reddit.com/r/pics/comments/304rms/cats_reaction_to_seeing_the_ceiling_fan_move_for/cpp2kkl</REDDIT></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('reddit');
				}
			],
			[
				'https://rumble.com/embed/v8bhte/',
				'<r><RUMBLE id="v8bhte">https://rumble.com/embed/v8bhte/</RUMBLE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('rumble');
				}
			],
			[
				'http://rutube.ru/tracks/4118278.html?v=8b490a46447720d4ad74616f5de2affd',
				'<r><RUTUBE id="4118278">http://rutube.ru/tracks/4118278.html?v=8b490a46447720d4ad74616f5de2affd</RUTUBE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('rutube');
				}
			],
			[
				'https://rutube.ru/play/embed/4782775',
				'<r><RUTUBE id="4782775">https://rutube.ru/play/embed/4782775</RUTUBE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('rutube');
				}
			],
			[
				'http://www.scribd.com/doc/233658242/Detect-Malware-w-Memory-Forensics',
				'<r><SCRIBD id="233658242">http://www.scribd.com/doc/233658242/Detect-Malware-w-Memory-Forensics</SCRIBD></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('scribd');
				}
			],
			[
				'https://www.scribd.com/document/237147661/Calculus-2-Test-1-Review?in_collection=5291376',
				'<r><SCRIBD id="237147661">https://www.scribd.com/document/237147661/Calculus-2-Test-1-Review?in_collection=5291376</SCRIBD></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('scribd');
				}
			],
			[
				'https://www.scribd.com/mobile/document/318498911/Kerbin-Times-8',
				'<r><SCRIBD id="318498911">https://www.scribd.com/mobile/document/318498911/Kerbin-Times-8</SCRIBD></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('scribd');
				}
			],
			[
				'https://www.scribd.com/presentation/324333881/600-1450-World-History-Jeopardy',
				'<r><SCRIBD id="324333881">https://www.scribd.com/presentation/324333881/600-1450-World-History-Jeopardy</SCRIBD></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('scribd');
				}
			],
			[
				'https://sendvid.com/w99u3jjp',
				'<r><SENDVID id="w99u3jjp">https://sendvid.com/w99u3jjp</SENDVID></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('sendvid');
				}
			],
			[
				'http://www.slideshare.net/Slideshare/how-23431564',
				'<r><SLIDESHARE id="23431564">http://www.slideshare.net/Slideshare/how-23431564</SLIDESHARE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('slideshare');
				}
			],
			[
				'https://api.soundcloud.com/tracks/168988860?secret_token=s-GT9Cd',
				'<r><SOUNDCLOUD id="tracks/168988860" secret_token="s-GT9Cd" track_id="168988860">https://api.soundcloud.com/tracks/168988860?secret_token=s-GT9Cd</SOUNDCLOUD></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('soundcloud');
				}
			],
			[
				'https://www.sporcle.com/framed/?v=8&pm&gid=15d32a898365&fid=5cd9efa1a3dc3&width=820',
				'<r><SPORCLE id="15d32a898365">https://www.sporcle.com/framed/?v=8&amp;pm&amp;gid=15d32a898365&amp;fid=5cd9efa1a3dc3&amp;width=820</SPORCLE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('sporcle');
				}
			],
			[
				'https://play.spotify.com/user/commodore-64/playlist/33fewoc4vDuICqL2mX95PA',
				'<r><SPOTIFY id="playlist/33fewoc4vDuICqL2mX95PA">https://play.spotify.com/user/commodore-64/playlist/33fewoc4vDuICqL2mX95PA</SPOTIFY></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('spotify');
				}
			],
			[
				'https://open.spotify.com/playlist/33fewoc4vDuICqL2mX95PA',
				'<r><SPOTIFY id="playlist/33fewoc4vDuICqL2mX95PA">https://open.spotify.com/playlist/33fewoc4vDuICqL2mX95PA</SPOTIFY></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('spotify');
				}
			],
			[
				'https://play.spotify.com/track/6acKqVtKngFXApjvXsU6mQ',
				'<r><SPOTIFY id="track/6acKqVtKngFXApjvXsU6mQ">https://play.spotify.com/track/6acKqVtKngFXApjvXsU6mQ</SPOTIFY></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('spotify');
				}
			],
			[
				'https://open.spotify.com/episode/37Tik1kHq7J2WcYcoLiypt',
				'<r><SPOTIFY id="episode/37Tik1kHq7J2WcYcoLiypt">https://open.spotify.com/episode/37Tik1kHq7J2WcYcoLiypt</SPOTIFY></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('spotify');
				}
			],
			[
				'https://spreaker.com/episode/12449095',
				'<r><SPREAKER episode_id="12449095">https://spreaker.com/episode/12449095</SPREAKER></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('spreaker');
				}
			],
			[
				'http://store.steampowered.com/app/517160/',
				'<r><STEAMSTORE id="517160">http://store.steampowered.com/app/517160/</STEAMSTORE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('steamstore');
				}
			],			[
				'https://app.stitcher.com/splayer/f/12645/37808629',
				'<r><STITCHER eid="37808629" fid="12645">https://app.stitcher.com/splayer/f/12645/37808629</STITCHER></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('stitcher');
				}
			],

			[
				'http://strawpoll.me/738091',
				'<r><STRAWPOLL id="738091">http://strawpoll.me/738091</STRAWPOLL></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('strawpoll');
				}
			],
			[
				'http://streamable.com/e4d',
				'<r><STREAMABLE id="e4d">http://streamable.com/e4d</STREAMABLE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('streamable');
				}
			],
			[
				'http://streamable.com/e/e4d',
				'<r><STREAMABLE id="e4d">http://streamable.com/e/e4d</STREAMABLE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('streamable');
				}
			],
			[
				'https://streamja.com/2nr',
				'<r><STREAMJA id="2nr">https://streamja.com/2nr</STREAMJA></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('streamja');
				}
			],
			[
				'https://streamja.com/terms',
				'<t>https://streamja.com/terms</t>'
			],
			[
				'http://teamcoco.com/video/73784/historian-a-scott-berg-serious-jibber-jabber-with-conan-obrien',
				'<r><TEAMCOCO id="73784">http://teamcoco.com/video/73784/historian-a-scott-berg-serious-jibber-jabber-with-conan-obrien</TEAMCOCO></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('teamcoco');
				}
			],
			[
				'http://www.ted.com/talks/eli_pariser_beware_online_filter_bubbles.html',
				'<r><TED id="talks/eli_pariser_beware_online_filter_bubbles.html">http://www.ted.com/talks/eli_pariser_beware_online_filter_bubbles.html</TED></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('ted');
				}
			],
			[
				'http://www.ted.com/talks/eli_pariser_beware_online_filter_bubbles/transcript',
				'<t>http://www.ted.com/talks/eli_pariser_beware_online_filter_bubbles/transcript</t>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('ted');
				}
			],
			[
				'https://t.me/durov/68',
				'<r><TELEGRAM id="durov/68">https://t.me/durov/68</TELEGRAM></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('telegram');
				}
			],
			[
				'https://t.me/durov',
				'<t>https://t.me/durov</t>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('telegram');
				}
			],
			[
				'https://t.me/addstickers/Xxxxxxxx',
				'<t>https://t.me/addstickers/Xxxxxxxx</t>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('telegram');
				}
			],
			[
				'https://t.me/joinchat/xxxxxxxxxxxxxxxxxxxxxx',
				'<t>https://t.me/joinchat/xxxxxxxxxxxxxxxxxxxxxx</t>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('telegram');
				}
			],
			[
				'http://www.theatlantic.com/video/index/358928/computer-vision-syndrome-and-you/',
				'<r><THEATLANTIC id="358928">http://www.theatlantic.com/video/index/358928/computer-vision-syndrome-and-you/</THEATLANTIC></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('theatlantic');
				}
			],
			[
				'http://www.theguardian.com/world/video/2016/apr/07/tokyos-hedgehog-cafe-encourages-you-to-embrace-prickly-pets-video',
				'<r><THEGUARDIAN id="world/video/2016/apr/07/tokyos-hedgehog-cafe-encourages-you-to-embrace-prickly-pets-video">http://www.theguardian.com/world/video/2016/apr/07/tokyos-hedgehog-cafe-encourages-you-to-embrace-prickly-pets-video</THEGUARDIAN></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('theguardian');
				}
			],
			[
				'http://www.theonion.com/video/nation-successfully-completes-mothers-day-by-918-a,35998/',
				'<r><THEONION id="35998">http://www.theonion.com/video/nation-successfully-completes-mothers-day-by-918-a,35998/</THEONION></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('theonion');
				}
			],
			[
				'https://www.tiktok.com/@lauren.feagans/video/6789430799839104261',
				'<r><TIKTOK id="6789430799839104261">https://www.tiktok.com/@lauren.feagans/video/6789430799839104261</TIKTOK></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('tiktok');
				}
			],
			[
				'https://m.tiktok.com/v/6789430799839104261.html',
				'<r><TIKTOK id="6789430799839104261">https://m.tiktok.com/v/6789430799839104261.html</TIKTOK></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('tiktok');
				}
			],
			[
				'http://www.tmz.com/videos/0_2pr9x3rb/',
				'<r><TMZ id="0_2pr9x3rb">http://www.tmz.com/videos/0_2pr9x3rb/</TMZ></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('tmz');
				}
			],
			[
				'https://www.tradingview.com/symbols/NASDAQ-AAPL/',
				'<r><TRADINGVIEW symbol="NASDAQ-AAPL">https://www.tradingview.com/symbols/NASDAQ-AAPL/</TRADINGVIEW></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('tradingview');
				}
			],
			[
				'https://www.tradingview.com/chart/US30/Yro4JNSD-us30/',
				'<r><TRADINGVIEW chart="Yro4JNSD">https://www.tradingview.com/chart/US30/Yro4JNSD-us30/</TRADINGVIEW></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('tradingview');
				}
			],
			[
				'https://www.tradingview.com/i/Yro4JNSD',
				'<r><TRADINGVIEW chart="Yro4JNSD">https://www.tradingview.com/i/Yro4JNSD</TRADINGVIEW></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('tradingview');
				}
			],
//			[
//				'http://www.traileraddict.com/tags/musical',
//				'<t>http://www.traileraddict.com/tags/musical</t>',
//				[],
//				function ($configurator)
//				{
//					$configurator->MediaEmbed->add('traileraddict');
//				}
//			],
			[
				'https://mydailyfreedom.com/tv/video/chick-fil-a-customers-sing-impromtu-lean-on-me-8213.html',
				'<r><TRENDINGVIEWS id="8213">https://mydailyfreedom.com/tv/video/chick-fil-a-customers-sing-impromtu-lean-on-me-8213.html</TRENDINGVIEWS></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('trendingviews');
				}
			],
			[
				'https://trendingviews.co/video/chick-fil-a-customers-sing-impromtu-lean-on-me-8213.html',
				'<r><TRENDINGVIEWS id="8213">https://trendingviews.co/video/chick-fil-a-customers-sing-impromtu-lean-on-me-8213.html</TRENDINGVIEWS></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('trendingviews');
				}
			],
			[
				'http://www.twitch.tv/playstation/v/3589809',
				'<r><TWITCH channel="playstation" video_id="3589809">http://www.twitch.tv/playstation/v/3589809</TWITCH></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('twitch');
				}
			],
			[
				'https://www.twitch.tv/videos/29415830',
				'<r><TWITCH video_id="29415830">https://www.twitch.tv/videos/29415830</TWITCH></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('twitch');
				}
			],
			[
				'https://clips.twitch.tv/twitch/HorribleWoodpeckerHassanChop',
				'<r><TWITCH channel="twitch" clip_id="HorribleWoodpeckerHassanChop">https://clips.twitch.tv/twitch/HorribleWoodpeckerHassanChop</TWITCH></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('twitch');
				}
			],
			[
				'https://clips.twitch.tv/AcceptableCooperativeYogurtTwitchRPG',
				'<r><TWITCH clip_id="AcceptableCooperativeYogurtTwitchRPG">https://clips.twitch.tv/AcceptableCooperativeYogurtTwitchRPG</TWITCH></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('twitch');
				}
			],
			[
				'https://www.twitch.tv/albiononline/clip/CleanWiseToothPrimeMe',
				'<r><TWITCH channel="albiononline" clip_id="CleanWiseToothPrimeMe">https://www.twitch.tv/albiononline/clip/CleanWiseToothPrimeMe</TWITCH></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('twitch');
				}
			],
			[
				'https://blog.twitch.tv/coming-soon-get-it-on-twitch-9c829cae6ac1',
				'<t>https://blog.twitch.tv/coming-soon-get-it-on-twitch-9c829cae6ac1</t>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('twitch');
				}
			],
			[
				'https://www.twitch.tv/chess/clip/CrepuscularOptimisticEagleOhMyDog-9jMi4oqxK5MN0F0C',
				'<r><TWITCH channel="chess" clip_id="CrepuscularOptimisticEagleOhMyDog-9jMi4oqxK5MN0F0C">https://www.twitch.tv/chess/clip/CrepuscularOptimisticEagleOhMyDog-9jMi4oqxK5MN0F0C</TWITCH></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('twitch');
				}
			],
			[
				'https://clips.twitch.tv/CrepuscularOptimisticEagleOhMyDog-9jMi4oqxK5MN0F0C',
				'<r><TWITCH clip_id="CrepuscularOptimisticEagleOhMyDog-9jMi4oqxK5MN0F0C">https://clips.twitch.tv/CrepuscularOptimisticEagleOhMyDog-9jMi4oqxK5MN0F0C</TWITCH></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('twitch');
				}
			],
			[
				'https://twitter.com/BarackObama/statuses/266031293945503744',
				'<r><TWITTER id="266031293945503744">https://twitter.com/BarackObama/statuses/266031293945503744</TWITTER></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('twitter');
				}
			],
			[
				'https://twitter.com/BarackObama/status/266031293945503744',
				'<r><TWITTER id="266031293945503744">https://twitter.com/BarackObama/status/266031293945503744</TWITTER></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('twitter');
				}
			],
			[
				'https://twitter.com/#!/BarackObama/status/266031293945503744',
				'<r><TWITTER id="266031293945503744">https://twitter.com/#!/BarackObama/status/266031293945503744</TWITTER></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('twitter');
				}
			],
			[
				'https://mobile.twitter.com/DerekTVShow/status/463372588690202624',
				'<r><TWITTER id="463372588690202624">https://mobile.twitter.com/DerekTVShow/status/463372588690202624</TWITTER></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('twitter');
				}
			],
			[
				'https://twitter.com/i/topics/tweet/463372588690202624',
				'<r><TWITTER id="463372588690202624">https://twitter.com/i/topics/tweet/463372588690202624</TWITTER></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('twitter');
				}
			],
			[
				'https://twitter.com/i/web/status/463372588690202624',
				'<r><TWITTER id="463372588690202624">https://twitter.com/i/web/status/463372588690202624</TWITTER></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('twitter');
				}
			],
			[
				'http://vbox7.com/play:a87a6894c5',
				'<r><VBOX7 id="a87a6894c5">http://vbox7.com/play:a87a6894c5</VBOX7></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('vbox7');
				}
			],
			[
				'http://www.vevo.com/watch/USUV71400682',
				'<r><VEVO id="USUV71400682">http://www.vevo.com/watch/USUV71400682</VEVO></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('vevo');
				}
			],
			[
				'https://www.vevo.com/watch/justin-timberlake/say-something-(official-video)/USRV81701472',
				'<r><VEVO id="USRV81701472">https://www.vevo.com/watch/justin-timberlake/say-something-(official-video)/USRV81701472</VEVO></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('vevo');
				}
			],
			[
				'http://www.videodetective.com/movies/deadpool/38876',
				'<r><VIDEODETECTIVE id="38876">http://www.videodetective.com/movies/deadpool/38876</VIDEODETECTIVE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('videodetective');
				}
			],
			[
				'http://www.videodetective.com/movies/NATURAL_BORN_KILLERS/trailer/P00005250.htm',
				'<r><VIDEODETECTIVE id="5250">http://www.videodetective.com/movies/NATURAL_BORN_KILLERS/trailer/P00005250.htm</VIDEODETECTIVE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('videodetective');
				}
			],
			[
				'http://vimeo.com/67207222',
				'<r><VIMEO id="67207222">http://vimeo.com/67207222</VIMEO></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('vimeo');
				}
			],
			[
				'https://player.vimeo.com/video/125956083',
				'<r><VIMEO id="125956083">https://player.vimeo.com/video/125956083</VIMEO></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('vimeo');
				}
			],
			[
				'http://vimeo.com/67207222#t=90s',
				'<r><VIMEO id="67207222" t="90">http://vimeo.com/67207222#t=90s</VIMEO></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('vimeo');
				}
			],
			[
				'http://vimeo.com/67207222#t=1m30s',
				'<r><VIMEO id="67207222" t="90">http://vimeo.com/67207222#t=1m30s</VIMEO></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('vimeo');
				}
			],
			[
				// Vimeo usernames can start with a digit
				// https://xenforo.com/community/threads/197454/
				'https://vimeo.com/42video',
				'<t>https://vimeo.com/42video</t>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('vimeo');
				}
			],
			[
				'https://vk.com/video121599878_165723901?hash=e06b0878046e1d32',
				'<r><VK hash="e06b0878046e1d32" oid="121599878" vid="165723901">https://vk.com/video121599878_165723901?hash=e06b0878046e1d32</VK></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('vk');
				}
			],
			[
				'https://vk.com/video_ext.php?oid=121599878&id=165723901&hash=e06b0878046e1d32',
				'<r><VK hash="e06b0878046e1d32" oid="121599878" vid="165723901">https://vk.com/video_ext.php?oid=121599878&amp;id=165723901&amp;hash=e06b0878046e1d32</VK></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('vk');
				}
			],
			[
				'http://www.ustream.tv/recorded/40771396',
				'<r><USTREAM vid="40771396">http://www.ustream.tv/recorded/40771396</USTREAM></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('ustream');
				}
			],
			[
				'http://www.ustream.tv/explore/education',
				'<t>http://www.ustream.tv/explore/education</t>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('ustream');
				}
			],
			[
				'http://www.ustream.tv/upcoming',
				'<t>http://www.ustream.tv/upcoming</t>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('ustream');
				}
			],
			[
				'http://www.veoh.com/watch/v6335577TeB8kyNR',
				'<r><VEOH id="6335577TeB8kyNR">http://www.veoh.com/watch/v6335577TeB8kyNR</VEOH></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('veoh');
				}
			],
			[
				'http://www.veoh.com/m/watch.php?v=v6335577TeB8kyNR',
				'<r><VEOH id="6335577TeB8kyNR">http://www.veoh.com/m/watch.php?v=v6335577TeB8kyNR</VEOH></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('veoh');
				}
			],
			[
				'http://vimeo.com/channels/staffpicks/67207222',
				'<r><VIMEO id="67207222">http://vimeo.com/channels/staffpicks/67207222</VIMEO></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('vimeo');
				}
			],
			[
				'https://vine.co/v/bYwPIluIipH',
				'<r><VINE id="bYwPIluIipH">https://vine.co/v/bYwPIluIipH</VINE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('vine');
				}
			],
			[
				'http://vocaroo.com/i/s0dRy3rZ47bf',
				'<r><VOCAROO id="s0dRy3rZ47bf">http://vocaroo.com/i/s0dRy3rZ47bf</VOCAROO></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('vocaroo');
				}
			],
			[
				'https://vocaroo.com/3OlvdB6eiiK',
				'<r><VOCAROO id="3OlvdB6eiiK">https://vocaroo.com/3OlvdB6eiiK</VOCAROO></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('vocaroo');
				}
			],
			[
				'https://voca.ro/3OlvdB6eiiK',
				'<r><VOCAROO id="3OlvdB6eiiK">https://voca.ro/3OlvdB6eiiK</VOCAROO></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('vocaroo');
				}
			],
			[
				'http://www.vox.com/2015/7/21/9005857/ant-man-marvel-apology-review#ooid=ltbzJkdTpKpE-O6hOfD3YJew3t3MppXb',
				'<r><VOX id="ltbzJkdTpKpE-O6hOfD3YJew3t3MppXb">http://www.vox.com/2015/7/21/9005857/ant-man-marvel-apology-review#ooid=ltbzJkdTpKpE-O6hOfD3YJew3t3MppXb</VOX></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('vox');
				}
			],
			[
				'https://www.washingtonpost.com/video/c/video/df229384-9216-11e6-bc00-1a9756d4111b',
				'<r><WASHINGTONPOST id="df229384-9216-11e6-bc00-1a9756d4111b">https://www.washingtonpost.com/video/c/video/df229384-9216-11e6-bc00-1a9756d4111b</WASHINGTONPOST></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('washingtonpost');
				}
			],
			[
				'http://www.washingtonpost.com/video/world/aurora-display-lights-up-the-night-sky-over-finland/2016/10/14/df229384-9216-11e6-bc00-1a9756d4111b_video.html',
				'<r><WASHINGTONPOST id="df229384-9216-11e6-bc00-1a9756d4111b">http://www.washingtonpost.com/video/world/aurora-display-lights-up-the-night-sky-over-finland/2016/10/14/df229384-9216-11e6-bc00-1a9756d4111b_video.html</WASHINGTONPOST></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('washingtonpost');
				}
			],
			[
				'http://www.worldstarhiphop.com/featured/71630',
				'<r><WSHH id="71630">http://www.worldstarhiphop.com/featured/71630</WSHH></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('wshh');
				}
			],
			[
				'http://live.wsj.com/#!09FB2B3B-583E-4284-99D8-FEF6C23BE4E2',
				'<r><WSJ id="09FB2B3B-583E-4284-99D8-FEF6C23BE4E2">http://live.wsj.com/#!09FB2B3B-583E-4284-99D8-FEF6C23BE4E2</WSJ></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('wsj');
				}
			],
			[
				'http://live.wsj.com/video/seahawks-qb-russell-wilson-on-super-bowl-win/9B3DF790-9D20-442C-B564-51524B06FD26.html',
				'<r><WSJ id="9B3DF790-9D20-442C-B564-51524B06FD26">http://live.wsj.com/video/seahawks-qb-russell-wilson-on-super-bowl-win/9B3DF790-9D20-442C-B564-51524B06FD26.html</WSJ></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('wsj');
				}
			],
			[
				'http://live.wsj.com/video/seth-rogen-emotional-appeal-over-alzheimer/3885A1E1-D5DE-443A-AA45-6A8F6BB8FBD8.html?mod=trending_now_video_4#!3885A1E1-D5DE-443A-AA45-6A8F6BB8FBD8',
				'<r><WSJ id="3885A1E1-D5DE-443A-AA45-6A8F6BB8FBD8">http://live.wsj.com/video/seth-rogen-emotional-appeal-over-alzheimer/3885A1E1-D5DE-443A-AA45-6A8F6BB8FBD8.html?mod=trending_now_video_4#!3885A1E1-D5DE-443A-AA45-6A8F6BB8FBD8</WSJ></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('wsj');
				}
			],
			[
				'http://www.wsj.com/video/nba-players-primp-with-pedicures/9E476D54-6A60-4F3F-ABC1-411014552DE6.html',
				'<r><WSJ id="9E476D54-6A60-4F3F-ABC1-411014552DE6">http://www.wsj.com/video/nba-players-primp-with-pedicures/9E476D54-6A60-4F3F-ABC1-411014552DE6.html</WSJ></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('wsj');
				}
			],
			[
				'http://xboxclips.com/dizturbd/e3a2d685-3e9f-454f-89bf-54ddea8f29b3',
				'<r><XBOXCLIPS id="e3a2d685-3e9f-454f-89bf-54ddea8f29b3" user="dizturbd">http://xboxclips.com/dizturbd/e3a2d685-3e9f-454f-89bf-54ddea8f29b3</XBOXCLIPS></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('xboxclips');
				}
			],
			[
				'http://xboxclips.com/Spl0inker/screenshots/ab54bfa2-b1c8-444f-94da-466b8283ffb9',
				'<t>http://xboxclips.com/Spl0inker/screenshots/ab54bfa2-b1c8-444f-94da-466b8283ffb9</t>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('xboxclips');
				}
			],
			[
				'https://gameclips.io/boulderBaby5568/035a50fa-2d54-4820-aa44-f0f43a873308',
				'<r><XBOXCLIPS id="035a50fa-2d54-4820-aa44-f0f43a873308" user="boulderBaby5568">https://gameclips.io/boulderBaby5568/035a50fa-2d54-4820-aa44-f0f43a873308</XBOXCLIPS></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('xboxclips');
				}
			],
			[
				'https://gameclips.io/game/1840411466/week',
				'<t>https://gameclips.io/game/1840411466/week</t>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('xboxclips');
				}
			],
			[
				'http://xboxdvr.com/gamer/LOXITANE/video/12463958',
				'<r><XBOXDVR id="12463958" user="LOXITANE">http://xboxdvr.com/gamer/LOXITANE/video/12463958</XBOXDVR></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('xboxdvr');
				}
			],
			[
				'http://gamerdvr.com/gamer/LOXITANE/video/12463958',
				'<r><XBOXDVR id="12463958" user="LOXITANE">http://gamerdvr.com/gamer/LOXITANE/video/12463958</XBOXDVR></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('xboxdvr');
				}
			],
			[
				'http://v.youku.com/v_show/id_XNzQwNjcxNDM2.html',
				'<r><YOUKU id="XNzQwNjcxNDM2">http://v.youku.com/v_show/id_XNzQwNjcxNDM2.html</YOUKU></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youku');
				}
			],
			[
				'http://m.youku.com/video/id_XNzQwNjcxNDM2.html',
				'<r><YOUKU id="XNzQwNjcxNDM2">http://m.youku.com/video/id_XNzQwNjcxNDM2.html</YOUKU></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youku');
				}
			],
			[
				'https://www.youmaker.com/video/b3ce8457-2cbe-4661-84ec-829fa8fe0754',
				'<r><YOUMAKER id="b3ce8457-2cbe-4661-84ec-829fa8fe0754">https://www.youmaker.com/video/b3ce8457-2cbe-4661-84ec-829fa8fe0754</YOUMAKER></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youmaker');
				}
			],
			[
				'https://www.youmaker.com/v/EnDXOWo8OOvQ',
				'<r><YOUMAKER id="EnDXOWo8OOvQ">https://www.youmaker.com/v/EnDXOWo8OOvQ</YOUMAKER></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youmaker');
				}
			],
			[
				'[media]http://www.youtube.com/watch?v=-cEzsCAzTak&feature=channel[/media]',
				'<r><YOUTUBE id="-cEzsCAzTak"><s>[media]</s>http://www.youtube.com/watch?v=-cEzsCAzTak&amp;feature=channel<e>[/media]</e></YOUTUBE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'xx [media url=http://www.youtube.com/watch?v=-cEzsCAzTak] xx',
				'<r>xx <YOUTUBE id="-cEzsCAzTak">[media url=http://www.youtube.com/watch?v=-cEzsCAzTak]</YOUTUBE> xx</r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'xx [media url=http://www.youtube.com/watch?v=-cEzsCAzTak /] xx',
				'<r>xx <YOUTUBE id="-cEzsCAzTak">[media url=http://www.youtube.com/watch?v=-cEzsCAzTak /]</YOUTUBE> xx</r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'[YOUTUBE]-cEzsCAzTak[/YOUTUBE]',
				'<r><YOUTUBE id="-cEzsCAzTak"><s>[YOUTUBE]</s>-cEzsCAzTak<e>[/YOUTUBE]</e></YOUTUBE></r>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('youtube', ['contentAttributes' => ['id']]);
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'[YOUTUBE]http://www.youtube.com/watch?v=-cEzsCAzTak&feature=channel[/YOUTUBE]',
				'<r><YOUTUBE id="-cEzsCAzTak"><s>[YOUTUBE]</s>http://www.youtube.com/watch?v=-cEzsCAzTak&amp;feature=channel<e>[/YOUTUBE]</e></YOUTUBE></r>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('youtube', ['contentAttributes' => ['url'], 'tagName' => 'MEDIA']);
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'[YOUTUBE]http://www.youtube.com/watch?feature=player_embedded&v=-cEzsCAzTak[/YOUTUBE]',
				'<r><YOUTUBE id="-cEzsCAzTak"><s>[YOUTUBE]</s>http://www.youtube.com/watch?feature=player_embedded&amp;v=-cEzsCAzTak<e>[/YOUTUBE]</e></YOUTUBE></r>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('youtube', ['contentAttributes' => ['url'], 'tagName' => 'MEDIA']);
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'[YOUTUBE]http://www.youtube.com/v/-cEzsCAzTak[/YOUTUBE]',
				'<r><YOUTUBE id="-cEzsCAzTak"><s>[YOUTUBE]</s>http://www.youtube.com/v/-cEzsCAzTak<e>[/YOUTUBE]</e></YOUTUBE></r>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('youtube', ['contentAttributes' => ['url'], 'tagName' => 'MEDIA']);
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'[YOUTUBE]http://youtu.be/-cEzsCAzTak[/YOUTUBE]',
				'<r><YOUTUBE id="-cEzsCAzTak"><s>[YOUTUBE]</s>http://youtu.be/-cEzsCAzTak<e>[/YOUTUBE]</e></YOUTUBE></r>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('youtube', ['contentAttributes' => ['url'], 'tagName' => 'MEDIA']);
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'Check this: http://www.youtube.com/watch?v=-cEzsCAzTak and that: http://example.com',
				'<r>Check this: <YOUTUBE id="-cEzsCAzTak"><URL url="http://www.youtube.com/watch?v=-cEzsCAzTak">http://www.youtube.com/watch?v=-cEzsCAzTak</URL></YOUTUBE> and that: <URL url="http://example.com">http://example.com</URL></r>',
				[],
				function ($configurator)
				{
					$configurator->Autolink;
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'http://www.youtube.com/watch?feature=player_detailpage&v=9bZkp7q19f0#t=113',
				'<r><YOUTUBE id="9bZkp7q19f0" t="113">http://www.youtube.com/watch?feature=player_detailpage&amp;v=9bZkp7q19f0#t=113</YOUTUBE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'http://www.youtube.com/watch?feature=player_detailpage&v=9bZkp7q19f0&t=113',
				'<r><YOUTUBE id="9bZkp7q19f0" t="113">http://www.youtube.com/watch?feature=player_detailpage&amp;v=9bZkp7q19f0&amp;t=113</YOUTUBE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'http://www.youtube.com/watch?v=wZZ7oFKsKzY&t=1h23m45s',
				'<r><YOUTUBE id="wZZ7oFKsKzY" t="5025">http://www.youtube.com/watch?v=wZZ7oFKsKzY&amp;t=1h23m45s</YOUTUBE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'http://www.youtube.com/watch?v=wZZ7oFKsKzY&t=23m45s',
				'<r><YOUTUBE id="wZZ7oFKsKzY" t="1425">http://www.youtube.com/watch?v=wZZ7oFKsKzY&amp;t=23m45s</YOUTUBE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'http://www.youtube.com/watch?v=wZZ7oFKsKzY&t=45s',
				'<r><YOUTUBE id="wZZ7oFKsKzY" t="45">http://www.youtube.com/watch?v=wZZ7oFKsKzY&amp;t=45s</YOUTUBE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'https://youtu.be/wI__53kBBKM?t=9h38m43s',
				'<r><YOUTUBE id="wI__53kBBKM" t="34723">https://youtu.be/wI__53kBBKM?t=9h38m43s</YOUTUBE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'https://youtu.be/wI__53kBBKM?t=9h38m',
				'<r><YOUTUBE id="wI__53kBBKM" t="34680">https://youtu.be/wI__53kBBKM?t=9h38m</YOUTUBE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'https://youtu.be/wI__53kBBKM?t=9h43s',
				'<r><YOUTUBE id="wI__53kBBKM" t="32443">https://youtu.be/wI__53kBBKM?t=9h43s</YOUTUBE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'https://youtu.be/wI__53kBBKM?t=9h',
				'<r><YOUTUBE id="wI__53kBBKM" t="32400">https://youtu.be/wI__53kBBKM?t=9h</YOUTUBE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'https://youtu.be/wI__53kBBKM?t=38m',
				'<r><YOUTUBE id="wI__53kBBKM" t="2280">https://youtu.be/wI__53kBBKM?t=38m</YOUTUBE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'http://www.youtube.com/watch?v=pC35x6iIPmo&list=PLOU2XLYxmsIIxJrlMIY5vYXAFcO5g83gA',
				'<r><YOUTUBE id="pC35x6iIPmo" list="PLOU2XLYxmsIIxJrlMIY5vYXAFcO5g83gA">http://www.youtube.com/watch?v=pC35x6iIPmo&amp;list=PLOU2XLYxmsIIxJrlMIY5vYXAFcO5g83gA</YOUTUBE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'http://www.youtube.com/watch?v=pC35x6iIPmo&list=PLOU2XLYxmsIIxJrlMIY5vYXAFcO5g83gA#t=123',
				'<r><YOUTUBE id="pC35x6iIPmo" list="PLOU2XLYxmsIIxJrlMIY5vYXAFcO5g83gA" t="123">http://www.youtube.com/watch?v=pC35x6iIPmo&amp;list=PLOU2XLYxmsIIxJrlMIY5vYXAFcO5g83gA#t=123</YOUTUBE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'http://www.youtube.com/watch_popup?v=qybUFnY7Y8w',
				'<r><YOUTUBE id="qybUFnY7Y8w">http://www.youtube.com/watch_popup?v=qybUFnY7Y8w</YOUTUBE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'http://www.youtube.com/attribution_link?a=JdfC0C9V6ZI&u=%2Fwatch%3Fv%3DEhxJLojIE_o%26feature%3Dshare',
				'<r><YOUTUBE id="EhxJLojIE_o">http://www.youtube.com/attribution_link?a=JdfC0C9V6ZI&amp;u=%2Fwatch%3Fv%3DEhxJLojIE_o%26feature%3Dshare</YOUTUBE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'https://www.youtube.com/playlist?list=PLagyxbJHFyL14ge59s3CSZkz_oFYFr6ob',
				'<r><YOUTUBE list="PLagyxbJHFyL14ge59s3CSZkz_oFYFr6ob">https://www.youtube.com/playlist?list=PLagyxbJHFyL14ge59s3CSZkz_oFYFr6ob</YOUTUBE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'https://www.youtube.com/shorts/QH2-TGUlwu4',
				'<r><YOUTUBE id="QH2-TGUlwu4">https://www.youtube.com/shorts/QH2-TGUlwu4</YOUTUBE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
		];
	}

	public function getRenderingTests()
	{
		return [
			[
				'http://www.amazon.ca/gp/product/B00GQT1LNO/',
				'<span data-s9e-mediaembed="amazon" style="display:inline-block;width:100%;max-width:120px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:200%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="background:url(https://www.amazon.com/favicon.ico) center no-repeat;border:0;height:100%;left:0;position:absolute;width:100%" src="//ws-na.assoc-amazon.com/widgets/cm?l=as1&amp;f=ifr&amp;o=15&amp;t=&amp;asins=B00GQT1LNO"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('amazon');
				}
			],
			[
				'http://www.amazon.ca/gp/product/B00GQT1LNO/',
				'<span data-s9e-mediaembed="amazon" style="display:inline-block;width:100%;max-width:120px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:200%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="background:url(https://www.amazon.com/favicon.ico) center no-repeat;border:0;height:100%;left:0;position:absolute;width:100%" src="//ws-na.assoc-amazon.com/widgets/cm?l=as1&amp;f=ifr&amp;o=15&amp;t=foo-20&amp;asins=B00GQT1LNO"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('amazon');
					$configurator->rendering->parameters['AMAZON_ASSOCIATE_TAG_CA'] = 'foo-20';
				}
			],
			[
				'http://www.amazon.ca/gp/product/B00GQT1LNO/ http://www.amazon.de/Netgear-WN3100RP-100PES-Repeater-integrierte-Steckdose/dp/B00ET2LTE6/',
				'<span data-s9e-mediaembed="amazon" style="display:inline-block;width:100%;max-width:120px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:200%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="background:url(https://www.amazon.com/favicon.ico) center no-repeat;border:0;height:100%;left:0;position:absolute;width:100%" src="//ws-na.assoc-amazon.com/widgets/cm?l=as1&amp;f=ifr&amp;o=15&amp;t=foo-20&amp;asins=B00GQT1LNO"></iframe></span></span> <span data-s9e-mediaembed="amazon" style="display:inline-block;width:100%;max-width:120px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:200%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="background:url(https://www.amazon.com/favicon.ico) center no-repeat;border:0;height:100%;left:0;position:absolute;width:100%" src="//ws-eu.assoc-amazon.com/widgets/cm?l=as1&amp;f=ifr&amp;o=3&amp;t=bar-20&amp;asins=B00ET2LTE6"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('amazon');
					$configurator->rendering->parameters['AMAZON_ASSOCIATE_TAG_CA'] = 'foo-20';
					$configurator->rendering->parameters['AMAZON_ASSOCIATE_TAG_DE'] = 'bar-20';
				}
			],
			[
				'http://www.amazon.co.jp/gp/product/B003AKZ6I8/',
				'<span data-s9e-mediaembed="amazon" style="display:inline-block;width:100%;max-width:120px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:200%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="background:url(https://www.amazon.com/favicon.ico) center no-repeat;border:0;height:100%;left:0;position:absolute;width:100%" src="//ws-fe.assoc-amazon.com/widgets/cm?l=as1&amp;f=ifr&amp;o=9&amp;t=&amp;asins=B003AKZ6I8"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('amazon');
				}
			],
			[
				'https://www.amazon.co.uk/Vans-Unisex-Adults-Classic-Trainers/dp/B000NSMITU/',
				'<span data-s9e-mediaembed="amazon" style="display:inline-block;width:100%;max-width:120px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:200%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="background:url(https://www.amazon.com/favicon.ico) center no-repeat;border:0;height:100%;left:0;position:absolute;width:100%" src="//ws-eu.assoc-amazon.com/widgets/cm?l=as1&amp;f=ifr&amp;o=2&amp;t=&amp;asins=B000NSMITU"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('amazon');
				}
			],
			[
				'http://www.amazon.com/dp/B002MUC0ZY',
				'<span data-s9e-mediaembed="amazon" style="display:inline-block;width:100%;max-width:120px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:200%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="background:url(https://www.amazon.com/favicon.ico) center no-repeat;border:0;height:100%;left:0;position:absolute;width:100%" src="//ws-na.assoc-amazon.com/widgets/cm?l=as1&amp;f=ifr&amp;o=1&amp;t=&amp;asins=B002MUC0ZY"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('amazon');
				}
			],
			[
				'http://www.amazon.de/Netgear-WN3100RP-100PES-Repeater-integrierte-Steckdose/dp/B00ET2LTE6/',
				'<span data-s9e-mediaembed="amazon" style="display:inline-block;width:100%;max-width:120px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:200%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="background:url(https://www.amazon.com/favicon.ico) center no-repeat;border:0;height:100%;left:0;position:absolute;width:100%" src="//ws-eu.assoc-amazon.com/widgets/cm?l=as1&amp;f=ifr&amp;o=3&amp;t=&amp;asins=B00ET2LTE6"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('amazon');
				}
			],
			[
				'http://www.amazon.in/Vans-Unisex-Authentic-Midnight-Sneakers/dp/B01I3LNWQG/',
				'<span data-s9e-mediaembed="amazon" style="display:inline-block;width:100%;max-width:120px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:200%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="background:url(https://www.amazon.com/favicon.ico) center no-repeat;border:0;height:100%;left:0;position:absolute;width:100%" src="//ws-eu.assoc-amazon.com/widgets/cm?l=as1&amp;f=ifr&amp;o=31&amp;t=&amp;asins=B01I3LNWQG"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('amazon');
				}
			],
			[
				'http://www.amazon.in/Vans-Unisex-Authentic-Midnight-Sneakers/dp/B01I3LNWQG/',
				'<span data-s9e-mediaembed="amazon" style="display:inline-block;width:100%;max-width:120px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:200%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="background:url(https://www.amazon.com/favicon.ico) center no-repeat;border:0;height:100%;left:0;position:absolute;width:100%" src="//ws-eu.assoc-amazon.com/widgets/cm?l=as1&amp;f=ifr&amp;o=31&amp;t=in-20&amp;asins=B01I3LNWQG"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('amazon');
					$configurator->rendering->parameters['AMAZON_ASSOCIATE_TAG_IN'] = 'in-20';
				}
			],
			[
				'http://www.amazon.es/Vans-OLD-SKOOL-BLACK-WHITE/dp/B000R3QPEA/',
				'<span data-s9e-mediaembed="amazon" style="display:inline-block;width:100%;max-width:120px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:200%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="background:url(https://www.amazon.com/favicon.ico) center no-repeat;border:0;height:100%;left:0;position:absolute;width:100%" src="//ws-eu.assoc-amazon.com/widgets/cm?l=as1&amp;f=ifr&amp;o=30&amp;t=es-20&amp;asins=B000R3QPEA"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('amazon');
					$configurator->rendering->parameters['AMAZON_ASSOCIATE_TAG_ES'] = 'es-20';
				}
			],
			[
				'http://www.amazon.fr/Vans-Authentic-Baskets-mixte-adulte/dp/B005NIKPAY/',
				'<span data-s9e-mediaembed="amazon" style="display:inline-block;width:100%;max-width:120px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:200%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="background:url(https://www.amazon.com/favicon.ico) center no-repeat;border:0;height:100%;left:0;position:absolute;width:100%" src="//ws-eu.assoc-amazon.com/widgets/cm?l=as1&amp;f=ifr&amp;o=8&amp;t=&amp;asins=B005NIKPAY"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('amazon');
				}
			],
			[
				'http://www.amazon.it/gp/product/B00JGOMIP6/',
				'<span data-s9e-mediaembed="amazon" style="display:inline-block;width:100%;max-width:120px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:200%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="background:url(https://www.amazon.com/favicon.ico) center no-repeat;border:0;height:100%;left:0;position:absolute;width:100%" src="//ws-eu.assoc-amazon.com/widgets/cm?l=as1&amp;f=ifr&amp;o=29&amp;t=&amp;asins=B00JGOMIP6"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('amazon');
				}
			],
			[
				'http://www.audiomack.com/album/hz-global/double-a-side-vol3',
				'<iframe data-s9e-mediaembed="audiomack" allowfullscreen="" loading="lazy" scrolling="no" src="https://www.audiomack.com/embed/album/hz-global/double-a-side-vol3" style="border:0;height:400px;max-width:900px;width:100%"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('audiomack');
				}
			],
			[
				'http://www.audiomack.com/song/random-2/buy-the-world-final-1',
				'<iframe data-s9e-mediaembed="audiomack" allowfullscreen="" loading="lazy" scrolling="no" src="https://www.audiomack.com/embed/song/random-2/buy-the-world-final-1" style="border:0;height:252px;max-width:900px;width:100%"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('audiomack');
				}
			],
			[
				'http://www.bbc.com/news/video_and_audio/must_see/42847060/calls-to-clean-off-banksy-mural-in-hull',
				'<span data-s9e-mediaembed="bbcnews" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//www.bbc.com/news/av-embeds/42847060"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('bbcnews');
				}
			],
			[
				'http://www.cbsnews.com/video/watch/?id=50156501n',
				'<span data-s9e-mediaembed="cbsnews" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:62.5%;padding-bottom:calc(56.25% + 40px)"><object data="//i.i.cbsi.com/cnwk.1d/av/video/cbsnews/atlantis2/cbsnews_player_embed.swf" style="height:100%;left:0;position:absolute;width:100%" type="application/x-shockwave-flash" typemustmatch=""><param name="allowfullscreen" value="true"><param name="flashvars" value="si=254&amp;contentValue=50156501"></object></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('cbsnews');
				}
			],
			[
				'https://www.cbsnews.com/video/is-carbonated-water-a-healthy-option/',
				'<span data-s9e-mediaembed="cbsnews" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" scrolling="no" src="https://www.cbsnews.com/embed/videos/is-carbonated-water-a-healthy-option/" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('cbsnews');
				}
			],
			[
				'http://www.dailymotion.com/video/x222z1',
				'<span data-s9e-mediaembed="dailymotion" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//www.dailymotion.com/embed/video/x222z1"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('dailymotion');
				}
			],
			[
				'http://www.dailymotion.com/video/x5e9eog?start=90',
				'<span data-s9e-mediaembed="dailymotion" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//www.dailymotion.com/embed/video/x5e9eog?start=90"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('dailymotion');
				}
			],
			[
				'http://www.democracynow.org/2014/7/2/dn_at_almedalen_week_at_swedens',
				'<span data-s9e-mediaembed="democracynow" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//www.democracynow.org/embed/story/2014/7/2/dn_at_almedalen_week_at_swedens"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('democracynow');
				}
			],
			[
				'http://www.democracynow.org/blog/2015/3/13/part_2_bruce_schneier_on_the',
				'<span data-s9e-mediaembed="democracynow" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//www.democracynow.org/embed/blog/2015/3/13/part_2_bruce_schneier_on_the"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('democracynow');
				}
			],
			[
				'http://www.democracynow.org/shows/2006/2/20',
				'<span data-s9e-mediaembed="democracynow" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//www.democracynow.org/embed/show/2006/2/20"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('democracynow');
				}
			],
			[
				'http://www.democracynow.org/2015/5/21/headlines',
				'<span data-s9e-mediaembed="democracynow" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//www.democracynow.org/embed/headlines/2015/5/21"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('democracynow');
				}
			],
			[
				'http://www.dumpert.nl/mediabase/6622577/4652b140/r_mi_gaillard_doet_halloween_prank.html',
				'<span data-s9e-mediaembed="dumpert" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" scrolling="no" src="//www.dumpert.nl/embed/6622577_4652b140" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('dumpert');
				}
			],
			[
				'https://www.dumpert.nl/item/6622577_4652b140',
				'<span data-s9e-mediaembed="dumpert" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" scrolling="no" src="//www.dumpert.nl/embed/6622577_4652b140" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('dumpert');
				}
			],
			[
				'https://www.facebook.com/video/video.php?v=10100658170103643',
				'<iframe data-s9e-mediaembed="facebook" allowfullscreen="" loading="lazy" onload="var c=new MessageChannel;c.port1.onmessage=function(e){style.height=e.data+\'px\'};contentWindow.postMessage(\'s9e:init\',\'https://s9e.github.io\',[c.port2])" scrolling="no" src="https://s9e.github.io/iframe/2/facebook.min.html#v10100658170103643" style="border:0;height:360px;max-width:640px;width:100%"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('facebook');
				}
			],
			[
				'https://www.facebook.com/FacebookDevelopers/posts/10151471074398553',
				'<iframe data-s9e-mediaembed="facebook" allowfullscreen="" loading="lazy" onload="var c=new MessageChannel;c.port1.onmessage=function(e){style.height=e.data+\'px\'};contentWindow.postMessage(\'s9e:init\',\'https://s9e.github.io\',[c.port2])" scrolling="no" src="https://s9e.github.io/iframe/2/facebook.min.html#p10151471074398553" style="border:0;height:360px;max-width:640px;width:100%"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('facebook');
				}
			],
//			[
//				'https://www.falstad.com/circuit/circuitjs.html?cct=$+1+0.000005+1.3241202019156522+43+5+50%0AR+736+368+736+304+0+0+40+3.3+0+0+0.5%0A162+736+368+736+416+2+default-led+1+0+0+0.01%0At+688+432+736+432+0+1+-0.2745603107888651+0.7370392451696097+100%0At+688+464+736+464+0+1+0.548427436901697+0.7373314588213099+100%0As+672+416+672+432+0+0+false%0As+672+448+672+464+0+0+false%0Aw+672+448+656+448+0%0Aw+672+416+656+416+0%0Aw+656+416+656+368+0%0Aw+656+368+736+368+0%0Aw+656+448+624+448+0%0Aw+624+448+624+368+0%0Aw+624+368+656+368+0%0Aw+736+480+736+528+0%0Ag+736+528+736+560+0%0Ar+688+432+672+432+0+1000%0Ar+688+464+672+464+0+1000%0Aw+736+416+800+416+0%0A162+800+416+880+416+2+default-led+1+0+0+0.01%0Ag+880+416+912+416+0%0A',
//				'<iframe data-s9e-mediaembed="falstad" allowfullscreen="" loading="lazy" scrolling="no" style="border:0;height:500px;max-height:90vh;width:100%" src="https://www.falstad.com/circuit/circuitjs.html?running=false&amp;cct=$+1+0.000005+1.3241202019156522+43+5+50%0AR+736+368+736+304+0+0+40+3.3+0+0+0.5%0A162+736+368+736+416+2+default-led+1+0+0+0.01%0At+688+432+736+432+0+1+-0.2745603107888651+0.7370392451696097+100%0At+688+464+736+464+0+1+0.548427436901697+0.7373314588213099+100%0As+672+416+672+432+0+0+false%0As+672+448+672+464+0+0+false%0Aw+672+448+656+448+0%0Aw+672+416+656+416+0%0Aw+656+416+656+368+0%0Aw+656+368+736+368+0%0Aw+656+448+624+448+0%0Aw+624+448+624+368+0%0Aw+624+368+656+368+0%0Aw+736+480+736+528+0%0Ag+736+528+736+560+0%0Ar+688+432+672+432+0+1000%0Ar+688+464+672+464+0+1000%0Aw+736+416+800+416+0%0A162+800+416+880+416+2+default-led+1+0+0+0.01%0Ag+880+416+912+416+0%0A"></iframe>',
//				[],
//				function ($configurator)
//				{
//					$configurator->MediaEmbed->add('falstad');
//				}
//			],
			[
				'https://www.falstad.com/circuit/circuitjs.html?ctz=CQAgjCAMB0l3BWK0BsCBMkAcYCcZMBmQyMAkBSCiyAKAHdxyAWLETN1qB97EL9Fk5s6jQcJCEszfiNoAnJullLwAdhRRJJBbwkd1mqmEiZd4lRbAatJs4qkyujw1sJnGL65pfpmVOgAXdiF2BB9pMKNwEABaQmgsSjVcNXQUXEhcZjBmGRhw6zAEQjUcUyxcQXA4HgNvELZ0cO4xUIaDZqNaYM6WsmUu22QUQlwUcXTBSlxcEVQsKTGwKRKliBNRVQaB9n9uACVVLhQnES19pADkBFoAc1V0NNUSfPvJSKflF1fuAA9wAhcOxyNYmsV+OBNABhMC0AGEawgwjaTToMCaGQYkDQ9DwkBJdgoYyQJDpFFYzQARThCMyYWBOSoU0h2KpeP8KLwgz46D8e2uJgA+l0hZAhUz0CLoAgxdAwGKRULhYRaJz2M8DL8-NdcCLworJdLZTBmIqpXrVZhgbsOnwGgFYPAwGxYgVrJRKGgkjkUNi4daPlibFYbI74KQ2Ak0lgUGpCJRY6NMJo4QB7JggU5Z-hZYFYRL+XAlAiYZhzMrIZ1aZRsFC0IA',
				'<iframe data-s9e-mediaembed="falstad" allowfullscreen="" loading="lazy" scrolling="no" style="border:0;height:500px;max-height:90vh;width:100%" src="https://www.falstad.com/circuit/circuitjs.html?running=false&amp;ctz=CQAgjCAMB0l3BWK0BsCBMkAcYCcZMBmQyMAkBSCiyAKAHdxyAWLETN1qB97EL9Fk5s6jQcJCEszfiNoAnJullLwAdhRRJJBbwkd1mqmEiZd4lRbAatJs4qkyujw1sJnGL65pfpmVOgAXdiF2BB9pMKNwEABaQmgsSjVcNXQUXEhcZjBmGRhw6zAEQjUcUyxcQXA4HgNvELZ0cO4xUIaDZqNaYM6WsmUu22QUQlwUcXTBSlxcEVQsKTGwKRKliBNRVQaB9n9uACVVLhQnES19pADkBFoAc1V0NNUSfPvJSKflF1fuAA9wAhcOxyNYmsV+OBNABhMC0AGEawgwjaTToMCaGQYkDQ9DwkBJdgoYyQJDpFFYzQARThCMyYWBOSoU0h2KpeP8KLwgz46D8e2uJgA+l0hZAhUz0CLoAgxdAwGKRULhYRaJz2M8DL8-NdcCLworJdLZTBmIqpXrVZhgbsOnwGgFYPAwGxYgVrJRKGgkjkUNi4daPlibFYbI74KQ2Ak0lgUGpCJRY6NMJo4QB7JggU5Z-hZYFYRL+XAlAiYZhzMrIZ1aZRsFC0IA"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('falstad');
				}
			],
			[
				'http://www.funnyordie.com/videos/bf313bd8b4/murdock-with-keith-david',
				'<span data-s9e-mediaembed="funnyordie" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" scrolling="no" src="//www.funnyordie.com/embed/bf313bd8b4" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('funnyordie');
				}
			],
			[
				'http://www.gamespot.com/destiny/videos/destiny-the-moon-trailer-6415176/',
				'<span data-s9e-mediaembed="gamespot" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" scrolling="no" src="//www.gamespot.com/videos/embed/6415176/" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('gamespot');
				}
			],
			[
				'https://gist.github.com/s9e/6806305',
				'<iframe data-s9e-mediaembed="gist" allowfullscreen="" loading="lazy" onload="var c=new MessageChannel;c.port1.onmessage=function(e){style.height=e.data+\'px\'};contentWindow.postMessage(\'s9e:init\',\'https://s9e.github.io\',[c.port2])" scrolling="" src="https://s9e.github.io/iframe/2/gist.min.html#s9e/6806305" style="border:0;height:180px;width:100%"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('gist');
				}
			],
			[
				'https://plus.google.com/110286587261352351537/posts/XMABm8rLvRW',
				'<iframe data-s9e-mediaembed="googleplus" allowfullscreen="" loading="lazy" onload="var c=new MessageChannel;c.port1.onmessage=function(e){style.height=e.data+\'px\'};contentWindow.postMessage(\'s9e:init\',\'https://s9e.github.io\',[c.port2])" scrolling="no" style="border:0;height:240px;max-width:450px;width:100%" src="https://s9e.github.io/iframe/2/googleplus.min.html#110286587261352351537/posts/XMABm8rLvRW"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('googleplus');
				}
			],
			[
				'https://plus.google.com/+TonyHawk/posts/C5TMsDZJWBd',
				'<iframe data-s9e-mediaembed="googleplus" allowfullscreen="" loading="lazy" onload="var c=new MessageChannel;c.port1.onmessage=function(e){style.height=e.data+\'px\'};contentWindow.postMessage(\'s9e:init\',\'https://s9e.github.io\',[c.port2])" scrolling="no" style="border:0;height:240px;max-width:450px;width:100%" src="https://s9e.github.io/iframe/2/googleplus.min.html#+TonyHawk/posts/C5TMsDZJWBd"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('googleplus');
				}
			],
			[
				'https://docs.google.com/spreadsheet/ccc?key=0An1aCHqyU7FqdGtBUDc1S1NNSWhqY3NidndIa1JuQWc#gid=70',
				'<iframe data-s9e-mediaembed="googlesheets" allowfullscreen="" loading="lazy" scrolling="no" style="border:0;height:500px;resize:vertical;width:100%" src="https://docs.google.com/spreadsheets/d/0An1aCHqyU7FqdGtBUDc1S1NNSWhqY3NidndIa1JuQWc/preview#gid=70"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('googlesheets');
				}
			],
			[
				'https://docs.google.com/spreadsheets/u/1/d/e/2PACX-1vQ7iYECa_ziPWygKBf9Og-PkQl5-gdnFRU-jP-z1FyjLfLXMAdRJN1J5Lx31NjnCq5lkRcL-T0Idh4_/pubhtml',
				'<iframe data-s9e-mediaembed="googlesheets" allowfullscreen="" loading="lazy" scrolling="no" style="border:0;height:500px;resize:vertical;width:100%" src="https://docs.google.com/spreadsheets/d/e/2PACX-1vQ7iYECa_ziPWygKBf9Og-PkQl5-gdnFRU-jP-z1FyjLfLXMAdRJN1J5Lx31NjnCq5lkRcL-T0Idh4_/pubhtml?widget=true&amp;headers=false#gid="></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('googlesheets');
				}
			],
			[
				'https://docs.google.com/spreadsheets/d/e/2PACX-1vSbbciMU7t5dCB8auk-nyK-p-25TJjgUrzBuMCAiq_2ZDqoMSFAlZCctIC7y3TXS71ys_MRdoAU4Jqr/pubchart?oid=1729348495',
				'<span data-s9e-mediaembed="googlesheets" style="display:inline-block;width:100%;max-width:600px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:62%"><iframe allowfullscreen="" loading="lazy" scrolling="no" src="https://docs.google.com/spreadsheets/d/e/2PACX-1vSbbciMU7t5dCB8auk-nyK-p-25TJjgUrzBuMCAiq_2ZDqoMSFAlZCctIC7y3TXS71ys_MRdoAU4Jqr/pubchart?oid=1729348495&amp;format=interactive" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('googlesheets');
				}
			],
			[
				'http://uk.ign.com/videos/2013/07/12/pokemon-x-version-pokemon-y-version-battle-trailer',
				'<span data-s9e-mediaembed="ign" style="display:inline-block;width:100%;max-width:468px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.196581%"><iframe allowfullscreen="" loading="lazy" scrolling="no" src="//widgets.ign.com/video/embed/content.html?url=http://uk.ign.com/videos/2013/07/12/pokemon-x-version-pokemon-y-version-battle-trailer" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('ign');
				}
			],
			[
				'http://i.imgur.com/u7Yo0Vy.gifv',
				'<iframe data-s9e-mediaembed="imgur" allowfullscreen="" loading="lazy" onload="var c=new MessageChannel;c.port1.onmessage=function(e){var d=e.data.split(\' \');style.height=d[0]+\'px\';style.width=d[1]+\'px\'};contentWindow.postMessage(\'s9e:init\',\'https://s9e.github.io\',[c.port2])" scrolling="no" style="border:0;height:400px;max-width:100%;width:542px" src="https://s9e.github.io/iframe/2/imgur.min.html#u7Yo0Vy"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('imgur');
				}
			],
			[
				'https://imgur.com/a/0I30l',
				'<iframe data-s9e-mediaembed="imgur" allowfullscreen="" loading="lazy" onload="var c=new MessageChannel;c.port1.onmessage=function(e){var d=e.data.split(\' \');style.height=d[0]+\'px\';style.width=d[1]+\'px\'};contentWindow.postMessage(\'s9e:init\',\'https://s9e.github.io\',[c.port2])" scrolling="no" style="border:0;height:400px;max-width:100%;width:542px" src="https://s9e.github.io/iframe/2/imgur.min.html#a/0I30l"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('imgur');
				}
			],
			[
				'http://www.indiegogo.com/projects/513633',
				'<span data-s9e-mediaembed="indiegogo" style="display:inline-block;width:100%;max-width:222px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:200.45045%"><iframe allowfullscreen="" loading="lazy" scrolling="no" src="//www.indiegogo.com/project/513633/embedded" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('indiegogo');
				}
			],
			[
				'https://cdnapisec.kaltura.com/html5/html5lib/v2.73.2/mwEmbedFrame.php/p/1674401/uiconf_id/23435151/entry_id/1_dtmilkcn?wid=_1674401&iframeembed=true&playerId=kaltura_player&entry_id=1_dtmilkcn',
				'<span data-s9e-mediaembed="kaltura" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:61.875%;padding-bottom:calc(56.25% + 36px)"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="https://cdnapisec.kaltura.com/p/1674401/sp/167440100/embedIframeJs/uiconf_id/23435151/partner_id/1674401?iframeembed=true&amp;entry_id=1_dtmilkcn"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('kaltura');
				}
			],
			[
				'http://www.kickstarter.com/projects/1869987317/wish-i-was-here-1?ref=',
				'<span data-s9e-mediaembed="kickstarter" style="display:inline-block;width:100%;max-width:220px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:190.909091%"><iframe allowfullscreen="" loading="lazy" scrolling="no" src="//www.kickstarter.com/projects/1869987317/wish-i-was-here-1/widget/card.html" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('kickstarter');
				}
			],
			[
				'http://www.kickstarter.com/projects/1869987317/wish-i-was-here-1/widget/video.html',
				'<span data-s9e-mediaembed="kickstarter" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" scrolling="no" src="//www.kickstarter.com/projects/1869987317/wish-i-was-here-1/widget/video.html" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('kickstarter');
				}
			],
			[
				'https://medium.com/@donnydonny/team-internet-is-about-to-win-net-neutrality-and-they-didnt-need-googles-help-e7e2cf9b8a95',
				'<iframe data-s9e-mediaembed="medium" allowfullscreen="" loading="lazy" onload="var c=new MessageChannel;c.port1.onmessage=function(e){style.height=e.data+\'px\'};contentWindow.postMessage(\'s9e:init\',\'https://s9e.github.io\',[c.port2])" scrolling="no" src="https://s9e.github.io/iframe/2/medium.min.html#e7e2cf9b8a95" style="border:0;height:316px;max-width:900px;width:100%"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('medium');
				}
			],
			[
				'https://player.megaphone.fm/LKN8165322853',
				'<iframe data-s9e-mediaembed="megaphone" allowfullscreen="" loading="lazy" scrolling="no" style="border:0;height:200px;max-width:900px;width:100%" src="https://player.megaphone.fm/LKN8165322853"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('megaphone');
					$configurator->rendering->parameters['MEDIAEMBED_THEME'] = 'dark';
				}
			],
			[
				'https://player.megaphone.fm/LKN8165322853',
				'<iframe data-s9e-mediaembed="megaphone" allowfullscreen="" loading="lazy" scrolling="no" style="border:0;height:200px;max-width:900px;width:100%" src="https://player.megaphone.fm/LKN8165322853?light=true"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('megaphone');
					$configurator->rendering->parameters['MEDIAEMBED_THEME'] = 'light';
				}
			],
			[
				'http://www.metacafe.com/watch/10785282/chocolate_treasure_chest_epic_meal_time/',
				'<span data-s9e-mediaembed="metacafe" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" scrolling="no" src="//www.metacafe.com/embed/10785282/" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('metacafe');
				}
			],
			[
				'https://www.nhl.com/video/korpikoski-scores-on-odd-man-rush/t-283069656/c-46322103',
				'<span data-s9e-mediaembed="nhl" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="https://www.nhl.com/video/embed/t-283069656/c-46322103?autostart=false"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('nhl');
				}
			],
			[
				'https://www.nhl.com/video/c-46299003',
				'<span data-s9e-mediaembed="nhl" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="https://www.nhl.com/video/embed/c-46299003?autostart=false"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('nhl');
				}
			],
			[
				'https://www.nhl.com/video/t-281748732?partnerId=as_nhl_20161104_67553116&adbid=794558584411426816&adbpl=tw&adbpr=50004938',
				'<span data-s9e-mediaembed="nhl" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="https://www.nhl.com/video/embed/t-281748732?autostart=false"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('nhl');
				}
			],
			[
				'https://www.orfium.com/album/24371/everybody-loves-kanye-totom/',
				'<iframe data-s9e-mediaembed="orfium" allowfullscreen="" loading="lazy" scrolling="no" src="https://www.orfium.com/embedded/album/24371" style="border:0;height:550px;max-width:900px;width:100%"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('orfium');
				}
			],
			[
				'https://www.orfium.com/live-set/614763/foof-no-lights-5-foof/',
				'<iframe data-s9e-mediaembed="orfium" allowfullscreen="" loading="lazy" scrolling="no" src="https://www.orfium.com/embedded/live-set/614763" style="border:0;height:275px;max-width:900px;width:100%"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('orfium');
				}
			],
			[
				'https://www.orfium.com/playlist/511651/electronic-live-sessions-creamtronic/',
				'<iframe data-s9e-mediaembed="orfium" allowfullscreen="" loading="lazy" scrolling="no" src="https://www.orfium.com/embedded/playlist/511651" style="border:0;height:275px;max-width:900px;width:100%"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('orfium');
				}
			],
			[
				'https://www.orfium.com/track/625367/the-ambience-of-the-goss-vistas/',
				'<iframe data-s9e-mediaembed="orfium" allowfullscreen="" loading="lazy" scrolling="no" src="https://www.orfium.com/embedded/track/625367" style="border:0;height:275px;max-width:900px;width:100%"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('orfium');
				}
			],
			[
				'http://rutube.ru/tracks/4118278.html?v=8b490a46447720d4ad74616f5de2affd',
				'<span data-s9e-mediaembed="rutube" style="display:inline-block;width:100%;max-width:720px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" scrolling="no" src="//rutube.ru/play/embed/4118278" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('rutube');
				}
			],
			[
				'http://www.slideshare.net/Slideshare/how-23431564',
				'<span data-s9e-mediaembed="slideshare" style="display:inline-block;width:100%;max-width:427px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:83.372365%"><iframe allowfullscreen="" loading="lazy" scrolling="no" src="//www.slideshare.net/slideshow/embed_code/23431564" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('slideshare');
				}
			],
			[
				'https://play.spotify.com/album/5OSzFvFAYuRh93WDNCTLEz',
				'<span data-s9e-mediaembed="spotify" style="display:inline-block;width:100%;max-width:320px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:125%;padding-bottom:calc(100% + 80px)"><iframe allow="encrypted-media" allowfullscreen="" loading="lazy" scrolling="no" src="https://open.spotify.com/embed/album/5OSzFvFAYuRh93WDNCTLEz" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('spotify');
				}
			],
			[
				'https://play.spotify.com/track/3lDpjvbifbmrmzWGE8F9zd',
				'<span data-s9e-mediaembed="spotify" style="display:inline-block;width:100%;max-width:320px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:125%;padding-bottom:calc(100% + 80px)"><iframe allow="encrypted-media" allowfullscreen="" loading="lazy" scrolling="no" src="https://open.spotify.com/embed/track/3lDpjvbifbmrmzWGE8F9zd" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('spotify');
				}
			],
			[
				'https://open.spotify.com/episode/37Tik1kHq7J2WcYcoLiypt',
				'<iframe data-s9e-mediaembed="spotify" allow="encrypted-media" allowfullscreen="" loading="lazy" scrolling="no" src="https://open.spotify.com/embed/episode/37Tik1kHq7J2WcYcoLiypt" style="border:0;height:152px;max-width:900px;width:100%"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('spotify');
				}
			],
			[
				'https://open.spotify.com/show/6BKcIaN8Mcjg6v4iYLnCNY',
				'<iframe data-s9e-mediaembed="spotify" allow="encrypted-media" allowfullscreen="" loading="lazy" scrolling="no" src="https://open.spotify.com/embed/show/6BKcIaN8Mcjg6v4iYLnCNY" style="border:0;height:152px;max-width:900px;width:100%"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('spotify');
				}
			],
			[
				'https://spreaker.com/episode/12449095',
				'<iframe data-s9e-mediaembed="spreaker" allowfullscreen="" loading="lazy" scrolling="no" src="https://widget.spreaker.com/player?episode_id=12449095&amp;show_id=&amp;theme=" style="border:0;height:200px;max-width:900px;width:100%"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('spreaker');
				}
			],
			[
				'https://spreaker.com/episode/12449095',
				'<iframe data-s9e-mediaembed="spreaker" allowfullscreen="" loading="lazy" scrolling="no" src="https://widget.spreaker.com/player?episode_id=12449095&amp;show_id=&amp;theme=dark" style="border:0;height:200px;max-width:900px;width:100%"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('spreaker');
					$configurator->rendering->parameters['MEDIAEMBED_THEME'] = 'dark';
				}
			],
			[
				'http://www.ted.com/talks/eli_pariser_beware_online_filter_bubbles.html',
				'<span data-s9e-mediaembed="ted" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//embed.ted.com/talks/eli_pariser_beware_online_filter_bubbles.html"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('ted');
				}
			],
			[
				'http://www.ted.com/talks/richard_ledgett_the_nsa_responds_to_edward_snowden_s_ted_talk',
				'<span data-s9e-mediaembed="ted" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//embed.ted.com/talks/richard_ledgett_the_nsa_responds_to_edward_snowden_s_ted_talk.html"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('ted');
				}
			],
			[
				'https://www.tradingview.com/symbols/NASDAQ-AAPL/',
				'<iframe data-s9e-mediaembed="tradingview" allowfullscreen="" loading="lazy" scrolling="no" style="border:0;height:400px;width:100%" src="https://s.tradingview.com/widgetembed/?interval=D&amp;symbol=NASDAQ:AAPL"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('tradingview');
				}
			],
			[
				'https://www.tradingview.com/chart/US30/Yro4JNSD-us30/',
				'<iframe data-s9e-mediaembed="tradingview" allowfullscreen="" loading="lazy" scrolling="no" style="border:0;height:400px;width:100%" src="https://s.tradingview.com/embed/Yro4JNSD"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('tradingview');
				}
			],
			[
				'http://www.twitch.tv/twitch',
				'<span data-s9e-mediaembed="twitch" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" onload="contentWindow.postMessage(\'\',\'https://s9e.github.io\')" scrolling="no" src="https://s9e.github.io/iframe/2/twitch.min.html#channel=twitch;clip_id=;t=;video_id=" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('twitch');
				}
			],
			[
				'http://www.twitch.tv/twitch/v/29415830',
				'<span data-s9e-mediaembed="twitch" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" onload="contentWindow.postMessage(\'\',\'https://s9e.github.io\')" scrolling="no" src="https://s9e.github.io/iframe/2/twitch.min.html#channel=twitch;clip_id=;t=;video_id=29415830" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('twitch');
				}
			],
			[
				'http://www.twitch.tv/twitch/v/29415830?t=17m17s',
				'<span data-s9e-mediaembed="twitch" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" onload="contentWindow.postMessage(\'\',\'https://s9e.github.io\')" scrolling="no" src="https://s9e.github.io/iframe/2/twitch.min.html#channel=twitch;clip_id=;t=17m17s;video_id=29415830" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('twitch');
				}
			],
			[
				'https://clips.twitch.tv/twitch/HorribleWoodpeckerHassanChop',
				'<span data-s9e-mediaembed="twitch" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" onload="contentWindow.postMessage(\'\',\'https://s9e.github.io\')" scrolling="no" src="https://s9e.github.io/iframe/2/twitch.min.html#channel=twitch;clip_id=HorribleWoodpeckerHassanChop;t=;video_id=" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('twitch');
				}
			],
			[
				'https://clips.twitch.tv/AcceptableCooperativeYogurtTwitchRPG',
				'<span data-s9e-mediaembed="twitch" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" onload="contentWindow.postMessage(\'\',\'https://s9e.github.io\')" scrolling="no" src="https://s9e.github.io/iframe/2/twitch.min.html#channel=;clip_id=AcceptableCooperativeYogurtTwitchRPG;t=;video_id=" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('twitch');
				}
			],
			[
				'https://twitter.com/BarackObama/statuses/266031293945503744',
				'<iframe data-s9e-mediaembed="twitter" allow="autoplay *" allowfullscreen="" loading="lazy" onload="var c=new MessageChannel;c.port1.onmessage=function(e){style.height=e.data+\'px\'};contentWindow.postMessage(\'s9e:init\',\'https://s9e.github.io\',[c.port2])" scrolling="no" src="https://s9e.github.io/iframe/2/twitter.min.html#266031293945503744" style="border:0;height:250px;max-width:550px;width:100%"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('twitter');
				}
			],
			[
				'https://twitter.com/BarackObama/statuses/266031293945503744',
				'<iframe data-s9e-mediaembed="twitter" allow="autoplay *" allowfullscreen="" loading="lazy" onload="var c=new MessageChannel;c.port1.onmessage=function(e){style.height=e.data+\'px\'};contentWindow.postMessage(\'s9e:init\',\'https://s9e.github.io\',[c.port2])" scrolling="no" src="https://s9e.github.io/iframe/2/twitter.min.html#266031293945503744dark" style="border:0;height:250px;max-width:550px;width:100%"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('twitter');
					$configurator->rendering->parameters['MEDIAEMBED_THEME'] = 'dark';
				}
			],
			[
				'http://www.ustream.tv/recorded/40771396',
				'<span data-s9e-mediaembed="ustream" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//www.ustream.tv/embed/recorded/40771396?html5ui"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('ustream');
				}
			],
			[
				'http://vimeo.com/67207222',
				'<span data-s9e-mediaembed="vimeo" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//player.vimeo.com/video/67207222"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('vimeo');
				}
			],
			[
				'https://vine.co/v/bYwPIluIipH',
				'<span data-s9e-mediaembed="vine" style="display:inline-block;width:100%;max-width:480px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:100%"><iframe allowfullscreen="" loading="lazy" scrolling="no" src="https://vine.co/v/bYwPIluIipH/embed/simple?audio=1" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('vine');
				}
			],
			[
				'https://old.vocaroo.com/i/s1HNgmYD4bIH',
				'<span data-s9e-mediaembed="vocaroo" style="display:inline-block;width:100%;max-width:148px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:29.72973%"><object data="//vocaroo.com/player.swf?playMediaID=s1HNgmYD4bIH&amp;autoplay=0" style="height:100%;left:0;position:absolute;width:100%" type="application/x-shockwave-flash" typemustmatch=""><param name="allowfullscreen" value="true"></object></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('vocaroo');
				}
			],
			[
				'https://vocaroo.com/3OlvdB6eiiK',
				'<span data-s9e-mediaembed="vocaroo" style="display:inline-block;width:100%;max-width:300px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:20%"><iframe allowfullscreen="" loading="lazy" scrolling="no" src="https://vocaroo.com/embed/3OlvdB6eiiK" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('vocaroo');
				}
			],
			[
				'[media]http://www.youtube.com/watch?v=-cEzsCAzTak&feature=channel[/media]',
				'<span data-s9e-mediaembed="youtube" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="background:url(https://i.ytimg.com/vi/-cEzsCAzTak/hqdefault.jpg) 50% 50% / cover;border:0;height:100%;left:0;position:absolute;width:100%" src="https://www.youtube.com/embed/-cEzsCAzTak"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'[YOUTUBE]-cEzsCAzTak[/YOUTUBE]',
				'<span data-s9e-mediaembed="youtube" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="background:url(https://i.ytimg.com/vi/-cEzsCAzTak/hqdefault.jpg) 50% 50% / cover;border:0;height:100%;left:0;position:absolute;width:100%" src="https://www.youtube.com/embed/-cEzsCAzTak"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('youtube', ['contentAttributes' => ['id']]);
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'[YOUTUBE]http://www.youtube.com/watch?v=-cEzsCAzTak&feature=channel[/YOUTUBE]',
				'<span data-s9e-mediaembed="youtube" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="background:url(https://i.ytimg.com/vi/-cEzsCAzTak/hqdefault.jpg) 50% 50% / cover;border:0;height:100%;left:0;position:absolute;width:100%" src="https://www.youtube.com/embed/-cEzsCAzTak"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('youtube', ['contentAttributes' => ['url'], 'tagName' => 'MEDIA']);
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'[YOUTUBE=http://www.youtube.com/watch?v=-cEzsCAzTak]Hi!',
				'<span data-s9e-mediaembed="youtube" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="background:url(https://i.ytimg.com/vi/-cEzsCAzTak/hqdefault.jpg) 50% 50% / cover;border:0;height:100%;left:0;position:absolute;width:100%" src="https://www.youtube.com/embed/-cEzsCAzTak"></iframe></span></span>Hi!',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('youtube', ['defaultAttribute' => 'url', 'contentAttributes' => ['url'], 'tagName' => 'MEDIA']);
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'Check this: http://www.youtube.com/watch?v=-cEzsCAzTak',
				'Check this: <span data-s9e-mediaembed="youtube" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="background:url(https://i.ytimg.com/vi/-cEzsCAzTak/hqdefault.jpg) 50% 50% / cover;border:0;height:100%;left:0;position:absolute;width:100%" src="https://www.youtube.com/embed/-cEzsCAzTak"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'Check this: http://www.youtube.com/watch?v=-cEzsCAzTak and that: http://example.com',
				'Check this: <span data-s9e-mediaembed="youtube" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="background:url(https://i.ytimg.com/vi/-cEzsCAzTak/hqdefault.jpg) 50% 50% / cover;border:0;height:100%;left:0;position:absolute;width:100%" src="https://www.youtube.com/embed/-cEzsCAzTak"></iframe></span></span> and that: <a href="http://example.com">http://example.com</a>',
				[],
				function ($configurator)
				{
					$configurator->Autolink;
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'http://www.youtube.com/watch?feature=player_detailpage&v=9bZkp7q19f0#t=113',
				'<span data-s9e-mediaembed="youtube" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="background:url(https://i.ytimg.com/vi/9bZkp7q19f0/hqdefault.jpg) 50% 50% / cover;border:0;height:100%;left:0;position:absolute;width:100%" src="https://www.youtube.com/embed/9bZkp7q19f0?start=113"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'http://www.youtube.com/watch?v=pC35x6iIPmo&list=PLOU2XLYxmsIIxJrlMIY5vYXAFcO5g83gA',
				'<span data-s9e-mediaembed="youtube" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="background:url(https://i.ytimg.com/vi/pC35x6iIPmo/hqdefault.jpg) 50% 50% / cover;border:0;height:100%;left:0;position:absolute;width:100%" src="https://www.youtube.com/embed/pC35x6iIPmo?list=PLOU2XLYxmsIIxJrlMIY5vYXAFcO5g83gA"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'http://www.youtube.com/watch?v=pC35x6iIPmo&list=PLOU2XLYxmsIIxJrlMIY5vYXAFcO5g83gA#t=123',
				'<span data-s9e-mediaembed="youtube" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="background:url(https://i.ytimg.com/vi/pC35x6iIPmo/hqdefault.jpg) 50% 50% / cover;border:0;height:100%;left:0;position:absolute;width:100%" src="https://www.youtube.com/embed/pC35x6iIPmo?list=PLOU2XLYxmsIIxJrlMIY5vYXAFcO5g83gA&amp;start=123"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'http://www.youtube.com/watch?v=wZZ7oFKsKzY&t=1h23m45s',
				'<span data-s9e-mediaembed="youtube" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="background:url(https://i.ytimg.com/vi/wZZ7oFKsKzY/hqdefault.jpg) 50% 50% / cover;border:0;height:100%;left:0;position:absolute;width:100%" src="https://www.youtube.com/embed/wZZ7oFKsKzY?start=5025"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'http://www.youtube.com/watch?v=wZZ7oFKsKzY&t=23m45s',
				'<span data-s9e-mediaembed="youtube" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="background:url(https://i.ytimg.com/vi/wZZ7oFKsKzY/hqdefault.jpg) 50% 50% / cover;border:0;height:100%;left:0;position:absolute;width:100%" src="https://www.youtube.com/embed/wZZ7oFKsKzY?start=1425"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'https://youtu.be/wI__53kBBKM?t=9h38m43s',
				'<span data-s9e-mediaembed="youtube" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="background:url(https://i.ytimg.com/vi/wI__53kBBKM/hqdefault.jpg) 50% 50% / cover;border:0;height:100%;left:0;position:absolute;width:100%" src="https://www.youtube.com/embed/wI__53kBBKM?start=34723"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'https://youtu.be/wI__53kBBKM?t=9h38m',
				'<span data-s9e-mediaembed="youtube" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="background:url(https://i.ytimg.com/vi/wI__53kBBKM/hqdefault.jpg) 50% 50% / cover;border:0;height:100%;left:0;position:absolute;width:100%" src="https://www.youtube.com/embed/wI__53kBBKM?start=34680"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'https://youtu.be/wI__53kBBKM?t=9h43s',
				'<span data-s9e-mediaembed="youtube" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="background:url(https://i.ytimg.com/vi/wI__53kBBKM/hqdefault.jpg) 50% 50% / cover;border:0;height:100%;left:0;position:absolute;width:100%" src="https://www.youtube.com/embed/wI__53kBBKM?start=32443"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'https://youtu.be/wI__53kBBKM?t=9h',
				'<span data-s9e-mediaembed="youtube" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="background:url(https://i.ytimg.com/vi/wI__53kBBKM/hqdefault.jpg) 50% 50% / cover;border:0;height:100%;left:0;position:absolute;width:100%" src="https://www.youtube.com/embed/wI__53kBBKM?start=32400"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'https://youtu.be/wI__53kBBKM?t=38m',
				'<span data-s9e-mediaembed="youtube" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="background:url(https://i.ytimg.com/vi/wI__53kBBKM/hqdefault.jpg) 50% 50% / cover;border:0;height:100%;left:0;position:absolute;width:100%" src="https://www.youtube.com/embed/wI__53kBBKM?start=2280"></iframe></span></span>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
		];
	}

	/**
	* @testdox Legacy rendering tests
	* @dataProvider getLegacyRenderingTests
	*/
	public function testLegacyRendering($xml, $html, $setup = null)
	{
		$setup($this->configurator);
		$this->assertSame($html, $this->configurator->rendering->getRenderer()->render($xml));
	}

	public function getLegacyRenderingTests()
	{
		return [
			[
				'<r><AUDIOMACK id="random-2/buy-the-world-final-1" mode="song">http://www.audiomack.com/song/random-2/buy-the-world-final-1</AUDIOMACK></r>',
				'<iframe data-s9e-mediaembed="audiomack" allowfullscreen="" loading="lazy" scrolling="no" src="https://www.audiomack.com/embed/song/random-2/buy-the-world-final-1" style="border:0;height:252px;max-width:900px;width:100%"></iframe>',
				function ($configurator)
				{
					$configurator->MediaEmbed->add('audiomack');
				}
			],
			[
				'<r><AUDIOMACK id="hz-global/double-a-side-vol3" mode="album">http://www.audiomack.com/album/hz-global/double-a-side-vol3</AUDIOMACK></r>',
				'<iframe data-s9e-mediaembed="audiomack" allowfullscreen="" loading="lazy" scrolling="no" src="https://www.audiomack.com/embed/album/hz-global/double-a-side-vol3" style="border:0;height:400px;max-width:900px;width:100%"></iframe>',
				function ($configurator)
				{
					$configurator->MediaEmbed->add('audiomack');
				}
			],
			[
				'<r><BBCNEWS ad_site="/news/business" playlist="/news/business-29149086A" poster="/media/images/77590000/jpg/_77590973_mapopgetty.jpg">http://www.bbc.com/news/business-29149086</BBCNEWS></r>',
				'<span data-s9e-mediaembed="bbcnews" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//www.bbc.com/news/av-embeds/29149086#"></iframe></span></span>',
				function ($configurator)
				{
					$configurator->MediaEmbed->add('bbcnews');
				}
			],
			[
				'<r><BBCNEWS id="p0784g1m/48141248">https://www.bbc.co.uk/news/av/world-africa-48141248/fifteen-year-old-nigerian-builds-small-scale-construction-machines</BBCNEWS></r>',
				'<span data-s9e-mediaembed="bbcnews" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//www.bbc.com/news/av-embeds/48141248"></iframe></span></span>',
				function ($configurator)
				{
					$configurator->MediaEmbed->add('bbcnews');
				}
			],
			[
				'<r><BRIGHTCOVE bckey="AQ~~,AAAB9mw57HE~,xU4DCdZtHhuIakVdyH5VnUosMOtC9a9v" bcpid="2869183374001" bctid="5045373183001">http://link.brightcove.com/services/player/bcpid4501318026001?bctid=5045373183001</BRIGHTCOVE></r>',
				'<span data-s9e-mediaembed="brightcove" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="https://link.brightcove.com/services/player/bcpid2869183374001?bckey=AQ~~,AAAB9mw57HE~,xU4DCdZtHhuIakVdyH5VnUosMOtC9a9v&amp;bctid=5045373183001&amp;secureConnections=true&amp;secureHTMLConnections=true&amp;autoStart=false&amp;height=360&amp;width=640"></iframe></span></span>',
				function ($configurator)
				{
					$configurator->MediaEmbed->add('brightcove');
				}
			],
			[
				'<CBSNEWS pid="B2AtjLUWB4Vj">http://www.cbsnews.com/videos/is-carbonated-water-a-healthy-option/</CBSNEWS>',
				'<span data-s9e-mediaembed="cbsnews" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:62.1875%;padding-bottom:calc(56.25% + 38px)"><object data="//www.cbsnews.com/common/video/cbsnews_player.swf" style="height:100%;left:0;position:absolute;width:100%" type="application/x-shockwave-flash" typemustmatch=""><param name="allowfullscreen" value="true"><param name="flashvars" value="pType=embed&amp;si=254&amp;pid=B2AtjLUWB4Vj"></object></span></span>',
				function ($configurator)
				{
					$configurator->MediaEmbed->add('cbsnews');
				}
			],
			[
				'<r><HUDL athlete="2067184" highlight="163744377">http://www.hudl.com/athlete/2067184/highlights/163744377</HUDL></r>',
				'<span data-s9e-mediaembed="hudl" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//www.hudl.com/embed/athlete/2067184/highlights/163744377"></iframe></span></span>',
				function ($configurator)
				{
					$configurator->MediaEmbed->add('hudl');
				}
			],
			[
				'<r><IMGUR id="jhEHi">https://imgur.com/jhEHi</IMGUR> <IMGUR id="jhEHi" type="album">https://imgur.com/gallery/jhEHi</IMGUR></r>',
				'<iframe data-s9e-mediaembed="imgur" allowfullscreen="" loading="lazy" onload="var c=new MessageChannel;c.port1.onmessage=function(e){var d=e.data.split(\' \');style.height=d[0]+\'px\';style.width=d[1]+\'px\'};contentWindow.postMessage(\'s9e:init\',\'https://s9e.github.io\',[c.port2])" scrolling="no" style="border:0;height:400px;max-width:100%;width:542px" src="https://s9e.github.io/iframe/2/imgur.min.html#jhEHi"></iframe> <iframe data-s9e-mediaembed="imgur" allowfullscreen="" loading="lazy" onload="var c=new MessageChannel;c.port1.onmessage=function(e){var d=e.data.split(\' \');style.height=d[0]+\'px\';style.width=d[1]+\'px\'};contentWindow.postMessage(\'s9e:init\',\'https://s9e.github.io\',[c.port2])" scrolling="no" style="border:0;height:400px;max-width:100%;width:542px" src="https://s9e.github.io/iframe/2/imgur.min.html#a/jhEHi"></iframe>',
				function ($configurator)
				{
					$configurator->MediaEmbed->add('imgur');
				}
			],
			[
				'<r><MSNBC id="n_farrow_moon_140709_257794">http://www.msnbc.com/ronan-farrow-daily/watch/thats-no-moon--300512323725</MSNBC></r>',
				'<span data-s9e-mediaembed="msnbc" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:68.75%;padding-bottom:calc(56.25% + 80px)"><iframe allowfullscreen="" loading="lazy" scrolling="no" src="//player.theplatform.com/p/7wvmTC/MSNBCEmbeddedOffSite?guid=n_farrow_moon_140709_257794" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span></span>',
				function ($configurator)
				{
					$configurator->MediaEmbed->add('msnbc');
				}
			],
			[
				'<r><ODYSEE id="8726b01100463c4e254a38c3108ef3e05791aeda" name="Deni-Juric-Goal-2-0-%C5%A0IBENIK-vs-SLAVEN-Apr21">https://odysee.com/@ozgoals:a/Deni-Juric-Goal-2-0-%C5%A0IBENIK-vs-SLAVEN-Apr21:8</ODYSEE></r>',
				'<span data-s9e-mediaembed="odysee" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="https://odysee.com/%24/embed/Deni-Juric-Goal-2-0-%C5%A0IBENIK-vs-SLAVEN-Apr21/8726b01100463c4e254a38c3108ef3e05791aeda"></iframe></span></span>',
				function ($configurator)
				{
					$configurator->MediaEmbed->add('odysee');
				}
			],
			[
				'<r><ODYSEE id="8726b01100463c4e254a38c3108ef3e05791aeda">https://odysee.com/@ozgoals:a/Deni-Juric-Goal-2-0-%C5%A0IBENIK-vs-SLAVEN-Apr21:8</ODYSEE></r>',
				'<span data-s9e-mediaembed="odysee" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="https://odysee.com/%24/embed/-/8726b01100463c4e254a38c3108ef3e05791aeda"></iframe></span></span>',
				function ($configurator)
				{
					$configurator->MediaEmbed->add('odysee');
				}
			],
			[
				'<r><REDDIT path="/r/pics/comments/304rms/cats_reaction_to_seeing_the_ceiling_fan_move_for/cpp2kkl">http://www.reddit.com/r/pics/comments/304rms/cats_reaction_to_seeing_the_ceiling_fan_move_for/cpp2kkl</REDDIT></r>',
				'<iframe data-s9e-mediaembed="reddit" allowfullscreen="" loading="lazy" onload="var c=new MessageChannel;c.port1.onmessage=function(e){style.height=e.data+\'px\'};contentWindow.postMessage(\'s9e:init\',\'https://s9e.github.io\',[c.port2])" scrolling="no" src="https://s9e.github.io/iframe/2/reddit.min.html#/r/pics/comments/304rms/cats_reaction_to_seeing_the_ceiling_fan_move_for/cpp2kkl" style="border:0;height:165px;max-width:800px;width:100%"></iframe>',
				function ($configurator)
				{
					$configurator->MediaEmbed->add('reddit');
				}
			],
			[
				'<r><SOUNDCLOUD id="https://soundcloud.com/andrewbird/three-white-horses">https://soundcloud.com/andrewbird/three-white-horses</SOUNDCLOUD></r>',
				'<iframe data-s9e-mediaembed="soundcloud" allowfullscreen="" loading="lazy" scrolling="no" src="https://w.soundcloud.com/player/?url=https://soundcloud.com/andrewbird/three-white-horses" style="border:0;height:166px;max-width:900px;width:100%"></iframe>',
				function ($configurator)
				{
					$configurator->MediaEmbed->add('soundcloud');
				}
			],
			[
				'<r><SPOTIFY path="user/commodore-64/playlist/33fewoc4vDuICqL2mX95PA">https://play.spotify.com/user/commodore-64/playlist/33fewoc4vDuICqL2mX95PA</SPOTIFY></r>',
				'<span data-s9e-mediaembed="spotify" style="display:inline-block;width:100%;max-width:320px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:125%;padding-bottom:calc(100% + 80px)"><iframe allow="encrypted-media" allowfullscreen="" loading="lazy" scrolling="no" src="https://open.spotify.com/embed/user/commodore-64/playlist/33fewoc4vDuICqL2mX95PA" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span></span>',
				function ($configurator)
				{
					$configurator->MediaEmbed->add('spotify');
				}
			],
			[
				'<r><SPOTIFY path="track/6acKqVtKngFXApjvXsU6mQ">https://play.spotify.com/track/6acKqVtKngFXApjvXsU6mQ</SPOTIFY></r>',
				'<span data-s9e-mediaembed="spotify" style="display:inline-block;width:100%;max-width:320px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:125%;padding-bottom:calc(100% + 80px)"><iframe allow="encrypted-media" allowfullscreen="" loading="lazy" scrolling="no" src="https://open.spotify.com/embed/track/6acKqVtKngFXApjvXsU6mQ" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span></span>',
				function ($configurator)
				{
					$configurator->MediaEmbed->add('spotify');
				}
			],
		];
	}

	/**
	* @testdox AMP rendering tests
	* @dataProvider getAmpRenderingTests
	* @group needs-network
	*/
	public function testAmpRendering(string $siteId, string $text, string $html, $setup = null)
	{
		$this->configurator->MediaEmbed->add($siteId);
		$this->configurator->tags[$siteId]->template = $this->configurator->MediaEmbed->defaultSites[$siteId]['amp']['template'];
		$this->configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
		if (isset($setup))
		{
			$setup($this->configurator);
		}

		extract($this->configurator->finalize());

		$this->assertSame($html, $renderer->render($parser->parse($text)));
	}

	public function getAmpRenderingTests()
	{
		return [
			[
				'dailymotion',
				'https://www.dailymotion.com/video/x5e9eog?start=90',
				'<amp-dailymotion layout="responsive" width="640" height="360" data-start="90" data-videoid="x5e9eog"></amp-dailymotion>'
			],
			[
				'facebook',
				'https://www.facebook.com/video/video.php?v=10150451523596807',
				'<amp-facebook layout="responsive" width="640" height="360" data-embed-as="video" data-href="https://www.facebook.com/user/videos/10150451523596807"></amp-facebook>'
			],
			[
				'facebook',
				'https://www.facebook.com/watch/?v=224353158889229',
				'<amp-facebook layout="responsive" width="640" height="360" data-embed-as="video" data-href="https://www.facebook.com/user/videos/224353158889229"></amp-facebook>'
			],
			[
				'facebook',
				'https://www.facebook.com/FacebookDevelopers/posts/10151471074398553',
				'<amp-facebook layout="responsive" width="640" height="360" data-href="https://www.facebook.com/FacebookDevelopers/posts/10151471074398553"></amp-facebook>'
			],
			[
				'gfycat',
				'https://gfycat.com/LoathsomeHarmfulJenny',
				'<amp-gfycat layout="responsive" width="950" height="534" data-gfyid="LoathsomeHarmfulJenny"></amp-gfycat>'
			],
			[
				'gist',
				'https://gist.github.com/JoshyPHP/0ee8433f5a9a779d08ef',
				'<amp-gist layout="fixed-height" height="180" data-gistid="0ee8433f5a9a779d08ef"></amp-gist>'
			],
			[
				'gist',
				'https://gist.github.com/6806305',
				'<amp-gist layout="fixed-height" height="180" data-gistid="6806305"></amp-gist>'
			],
			[
				'gist',
				'https://gist.github.com/JoshyPHP/6806305/ad88d904b082c8211afa040162402015aacb8599',
				'<amp-gist layout="fixed-height" height="180" data-gistid="6806305"></amp-gist>'
			],
			[
				'imgur',
				'https://imgur.com/a/9UGCL',
				'<amp-imgur layout="responsive" width="542" height="400" data-imgur-id="a/9UGCL"></amp-imgur>'
			],
			[
				'instagram',
				'https://instagram.com/p/gbGaIXBQbn/',
				'<amp-instagram layout="responsive" width="540" height="540" data-captioned="" data-shortcode="gbGaIXBQbn"></amp-instagram>'
			],
			[
				'izlesene',
				'https://www.izlesene.com/video/kardan-adamin-salincak-keyfi/10553555',
				'<amp-izlesene layout="responsive" width="640" height="360" data-videoid="10553555"></amp-izlesene>'
			],
			[
				'kaltura',
				'https://www.kaltura.com/index.php/extwidget/preview/partner_id/1581781/uiconf_id/20490561/entry_id/0_ekjebl7h/embed/iframe',
				'<amp-kaltura-player layout="responsive" width="640" height="360" data-service-url="cdnapisec.kaltura.com" data-uiconf="20490561" data-partner="1581781" data-entryid="0_ekjebl7h" data-param-streamertype="auto"></amp-kaltura-player>'
			],
			[
				'megaphone',
				'https://player.megaphone.fm/LKN8165322853',
				'<amp-megaphone layout="fixed-height" height="200" data-episode="LKN8165322853"></amp-megaphone>'
			],
			[
				'megaphone',
				'https://player.megaphone.fm/LKN8165322853',
				'<amp-megaphone layout="fixed-height" height="200" data-episode="LKN8165322853" data-light=""></amp-megaphone>',
				function ($configurator)
				{
					$configurator->rendering->parameters['MEDIAEMBED_THEME'] = 'light';
				}
			],
			[
				'pinterest',
				'https://www.pinterest.com/pin/99360735500167749/',
				'<amp-pinterest width="245" height="330" data-do="embedPin" data-url="https://www.pinterest.com/pin/99360735500167749/"></amp-pinterest>'
			],
			[
				'reddit',
				'https://www.reddit.com/r/pics/comments/9wp1qg/my_mom_recently_started_watercolor_painting_and/',
				'<amp-reddit layout="responsive" width="450" height="300" data-embedtype="post" data-src="https://www.reddit.com/r/pics/comments/9wp1qg/"></amp-reddit>'
			],
			[
				'soundcloud',
				'https://api.soundcloud.com/tracks/98282116',
				'<amp-soundcloud layout="fixed-height" height="150" data-playlistid="" data-secret-token="" data-trackid="98282116"></amp-soundcloud>'
			],
			[
				'soundcloud',
				'https://soundcloud.com/tenaciousd/sets/rize-of-the-fenix/',
				'<amp-soundcloud layout="fixed-height" height="150" data-playlistid="1919974" data-secret-token="" data-trackid="44564704"></amp-soundcloud>'
			],
			[
				'twitter',
				'https://twitter.com/IJasonAlexander/statuses/526635414338023424',
				'<amp-twitter layout="responsive" width="550" height="250" data-tweetid="526635414338023424"><blockquote placeholder=""><a href="https://twitter.com/user/status/526635414338023424">Twitter</a></blockquote></amp-twitter>'
			],
			[
				'vimeo',
				'https://vimeo.com/67207222',
				'<amp-vimeo layout="responsive" width="640" height="360" data-videoid="67207222"></amp-vimeo>'
			],
			[
				'vine',
				'https://vine.co/v/bYwPIluIipH',
				'<amp-vine layout="responsive" width="400" height="400" data-vineid="bYwPIluIipH"></amp-vine>'
			],
			[
				'wistia',
				'https://thoughtworks.wistia.com/medias/b6al55s35k',
				'<amp-wistia-player layout="responsive" width="640" height="360" data-media-hashed-id="b6al55s35k"></amp-wistia-player>'
			],
			[
				'youtube',
				'http://www.youtube.com/watch?v=wZZ7oFKsKzY',
				'<amp-youtube layout="responsive" width="640" height="360" data-param-list="" data-param-start="" data-videoid="wZZ7oFKsKzY"></amp-youtube>'
			],
			[
				'youtube',
				'http://www.youtube.com/watch?v=wZZ7oFKsKzY&t=23m45s',
				'<amp-youtube layout="responsive" width="640" height="360" data-param-list="" data-param-start="1425" data-videoid="wZZ7oFKsKzY"></amp-youtube>'
			],
			[
				'youtube',
				'http://www.youtube.com/watch?v=pC35x6iIPmo&list=PLOU2XLYxmsIIxJrlMIY5vYXAFcO5g83gA',
				'<amp-youtube layout="responsive" width="640" height="360" data-param-list="PLOU2XLYxmsIIxJrlMIY5vYXAFcO5g83gA" data-param-start="" data-videoid="pC35x6iIPmo"></amp-youtube>'
			],
		];
	}
}