<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Emoji;

abstract class Helper
{
	protected static $map = array('#⃣'=>':hash:','0⃣'=>':zero:','1⃣'=>':one:','2⃣'=>':two:','3⃣'=>':three:','4⃣'=>':four:','5⃣'=>':five:','6⃣'=>':six:','7⃣'=>':seven:','8⃣'=>':eight:','9⃣'=>':nine:','©'=>':copyright:','®'=>':registered:','‼'=>':bangbang:','⁉'=>':interrobang:','™'=>':tm:','ℹ'=>':information_source:','↔'=>':left_right_arrow:','↕'=>':arrow_up_down:','↖'=>':arrow_upper_left:','↗'=>':arrow_upper_right:','↘'=>':arrow_lower_right:','↙'=>':arrow_lower_left:','↩'=>':leftwards_arrow_with_hook:','↪'=>':arrow_right_hook:','⌚'=>':watch:','⌛'=>':hourglass:','⏩'=>':fast_forward:','⏪'=>':rewind:','⏫'=>':arrow_double_up:','⏬'=>':arrow_double_down:','⏰'=>':alarm_clock:','⏳'=>':hourglass_flowing_sand:','Ⓜ'=>':m:','▪'=>':black_small_square:','▫'=>':white_small_square:','▶'=>':arrow_forward:','◀'=>':arrow_backward:','◻'=>':white_medium_square:','◼'=>':black_medium_square:','◽'=>':white_medium_small_square:','◾'=>':black_medium_small_square:','☀'=>':sunny:','☁'=>':cloud:','☎'=>':telephone:','☑'=>':ballot_box_with_check:','☔'=>':umbrella:','☕'=>':coffee:','☝'=>':point_up:','☺'=>':relaxed:','♈'=>':aries:','♉'=>':taurus:','♊'=>':gemini:','♋'=>':cancer:','♌'=>':leo:','♍'=>':virgo:','♎'=>':libra:','♏'=>':scorpius:','♐'=>':sagittarius:','♑'=>':capricorn:','♒'=>':aquarius:','♓'=>':pisces:','♠'=>':spades:','♣'=>':clubs:','♥'=>':hearts:','♦'=>':diamonds:','♨'=>':hotsprings:','♻'=>':recycle:','♿'=>':wheelchair:','⚓'=>':anchor:','⚠'=>':warning:','⚡'=>':zap:','⚪'=>':white_circle:','⚫'=>':black_circle:','⚽'=>':soccer:','⚾'=>':baseball:','⛄'=>':snowman:','⛅'=>':partly_sunny:','⛎'=>':ophiuchus:','⛔'=>':no_entry:','⛪'=>':church:','⛲'=>':fountain:','⛳'=>':golf:','⛵'=>':sailboat:','⛺'=>':tent:','⛽'=>':fuelpump:','✂'=>':scissors:','✅'=>':white_check_mark:','✈'=>':airplane:','✉'=>':envelope:','✊'=>':fist:','✋'=>':raised_hand:','✌'=>':v:','✏'=>':pencil2:','✒'=>':black_nib:','✔'=>':heavy_check_mark:','✖'=>':heavy_multiplication_x:','✨'=>':sparkles:','✳'=>':eight_spoked_asterisk:','✴'=>':eight_pointed_black_star:','❄'=>':snowflake:','❇'=>':sparkle:','❌'=>':x:','❎'=>':negative_squared_cross_mark:','❓'=>':question:','❔'=>':grey_question:','❕'=>':grey_exclamation:','❗'=>':exclamation:','❤'=>':heart:','➕'=>':heavy_plus_sign:','➖'=>':heavy_minus_sign:','➗'=>':heavy_division_sign:','➡'=>':arrow_right:','➰'=>':curly_loop:','⤴'=>':arrow_heading_up:','⤵'=>':arrow_heading_down:','⬅'=>':arrow_left:','⬆'=>':arrow_up:','⬇'=>':arrow_down:','⬛'=>':black_large_square:','⬜'=>':white_large_square:','⭐'=>':star:','⭕'=>':o:','〰'=>':wavy_dash:','〽'=>':part_alternation_mark:','㊗'=>':congratulations:','㊙'=>':secret:','🀄'=>':mahjong:','🃏'=>':black_joker:','🅰'=>':a:','🅱'=>':b:','🅾'=>':o2:','🅿'=>':parking:','🆎'=>':ab:','🆑'=>':cl:','🆒'=>':cool:','🆓'=>':free:','🆔'=>':id:','🆕'=>':new:','🆖'=>':ng:','🆗'=>':ok:','🆘'=>':sos:','🆙'=>':up:','🆚'=>':vs:','🇨🇳'=>':cn:','🇩🇪'=>':de:','🇪🇸'=>':es:','🇫🇷'=>':fr:','🇬🇧'=>':gb:','🇮🇹'=>':it:','🇯🇵'=>':jp:','🇰🇷'=>':kr:','🇺🇸'=>':us:','🇷🇺'=>':ru:','🈁'=>':koko:','🈂'=>':sa:','🈚'=>':u7121:','🈯'=>':u6307:','🈲'=>':u7981:','🈳'=>':u7a7a:','🈴'=>':u5408:','🈵'=>':u6e80:','🈶'=>':u6709:','🈷'=>':u6708:','🈸'=>':u7533:','🈹'=>':u5272:','🈺'=>':u55b6:','🉐'=>':ideograph_advantage:','🉑'=>':accept:','🌀'=>':cyclone:','🌁'=>':foggy:','🌂'=>':closed_umbrella:','🌃'=>':night_with_stars:','🌄'=>':sunrise_over_mountains:','🌅'=>':sunrise:','🌆'=>':city_dusk:','🌇'=>':city_sunset:','🌈'=>':rainbow:','🌉'=>':bridge_at_night:','🌊'=>':ocean:','🌋'=>':volcano:','🌌'=>':milky_way:','🌏'=>':earth_asia:','🌑'=>':new_moon:','🌓'=>':first_quarter_moon:','🌔'=>':waxing_gibbous_moon:','🌕'=>':full_moon:','🌙'=>':crescent_moon:','🌛'=>':first_quarter_moon_with_face:','🌟'=>':star2:','🌠'=>':stars:','🌰'=>':chestnut:','🌱'=>':seedling:','🌴'=>':palm_tree:','🌵'=>':cactus:','🌷'=>':tulip:','🌸'=>':cherry_blossom:','🌹'=>':rose:','🌺'=>':hibiscus:','🌻'=>':sunflower:','🌼'=>':blossom:','🌽'=>':corn:','🌾'=>':ear_of_rice:','🌿'=>':herb:','🍀'=>':four_leaf_clover:','🍁'=>':maple_leaf:','🍂'=>':fallen_leaf:','🍃'=>':leaves:','🍄'=>':mushroom:','🍅'=>':tomato:','🍆'=>':eggplant:','🍇'=>':grapes:','🍈'=>':melon:','🍉'=>':watermelon:','🍊'=>':tangerine:','🍌'=>':banana:','🍍'=>':pineapple:','🍎'=>':apple:','🍏'=>':green_apple:','🍑'=>':peach:','🍒'=>':cherries:','🍓'=>':strawberry:','🍔'=>':hamburger:','🍕'=>':pizza:','🍖'=>':meat_on_bone:','🍗'=>':poultry_leg:','🍘'=>':rice_cracker:','🍙'=>':rice_ball:','🍚'=>':rice:','🍛'=>':curry:','🍜'=>':ramen:','🍝'=>':spaghetti:','🍞'=>':bread:','🍟'=>':fries:','🍠'=>':sweet_potato:','🍡'=>':dango:','🍢'=>':oden:','🍣'=>':sushi:','🍤'=>':fried_shrimp:','🍥'=>':fish_cake:','🍦'=>':icecream:','🍧'=>':shaved_ice:','🍨'=>':ice_cream:','🍩'=>':doughnut:','🍪'=>':cookie:','🍫'=>':chocolate_bar:','🍬'=>':candy:','🍭'=>':lollipop:','🍮'=>':custard:','🍯'=>':honey_pot:','🍰'=>':cake:','🍱'=>':bento:','🍲'=>':stew:','🍳'=>':egg:','🍴'=>':fork_and_knife:','🍵'=>':tea:','🍶'=>':sake:','🍷'=>':wine_glass:','🍸'=>':cocktail:','🍹'=>':tropical_drink:','🍺'=>':beer:','🍻'=>':beers:','🎀'=>':ribbon:','🎁'=>':gift:','🎂'=>':birthday:','🎃'=>':jack_o_lantern:','🎄'=>':christmas_tree:','🎅'=>':santa:','🎆'=>':fireworks:','🎇'=>':sparkler:','🎈'=>':balloon:','🎉'=>':tada:','🎊'=>':confetti_ball:','🎋'=>':tanabata_tree:','🎌'=>':crossed_flags:','🎍'=>':bamboo:','🎎'=>':dolls:','🎏'=>':flags:','🎐'=>':wind_chime:','🎑'=>':rice_scene:','🎒'=>':school_satchel:','🎓'=>':mortar_board:','🎠'=>':carousel_horse:','🎡'=>':ferris_wheel:','🎢'=>':roller_coaster:','🎣'=>':fishing_pole_and_fish:','🎤'=>':microphone:','🎥'=>':movie_camera:','🎦'=>':cinema:','🎧'=>':headphones:','🎨'=>':art:','🎩'=>':tophat:','🎪'=>':circus_tent:','🎫'=>':ticket:','🎬'=>':clapper:','🎭'=>':performing_arts:','🎮'=>':video_game:','🎯'=>':dart:','🎰'=>':slot_machine:','🎱'=>':8ball:','🎲'=>':game_die:','🎳'=>':bowling:','🎴'=>':flower_playing_cards:','🎵'=>':musical_note:','🎶'=>':notes:','🎷'=>':saxophone:','🎸'=>':guitar:','🎹'=>':musical_keyboard:','🎺'=>':trumpet:','🎻'=>':violin:','🎼'=>':musical_score:','🎽'=>':running_shirt_with_sash:','🎾'=>':tennis:','🎿'=>':ski:','🏀'=>':basketball:','🏁'=>':checkered_flag:','🏂'=>':snowboarder:','🏃'=>':runner:','🏄'=>':surfer:','🏆'=>':trophy:','🏈'=>':football:','🏊'=>':swimmer:','🏠'=>':house:','🏡'=>':house_with_garden:','🏢'=>':office:','🏣'=>':post_office:','🏥'=>':hospital:','🏦'=>':bank:','🏧'=>':atm:','🏨'=>':hotel:','🏩'=>':love_hotel:','🏪'=>':convenience_store:','🏫'=>':school:','🏬'=>':department_store:','🏭'=>':factory:','🏮'=>':izakaya_lantern:','🏯'=>':japanese_castle:','🏰'=>':european_castle:','🐌'=>':snail:','🐍'=>':snake:','🐎'=>':racehorse:','🐑'=>':sheep:','🐒'=>':monkey:','🐔'=>':chicken:','🐗'=>':boar:','🐘'=>':elephant:','🐙'=>':octopus:','🐚'=>':shell:','🐛'=>':bug:','🐜'=>':ant:','🐝'=>':bee:','🐞'=>':beetle:','🐟'=>':fish:','🐠'=>':tropical_fish:','🐡'=>':blowfish:','🐢'=>':turtle:','🐣'=>':hatching_chick:','🐤'=>':baby_chick:','🐥'=>':hatched_chick:','🐦'=>':bird:','🐧'=>':penguin:','🐨'=>':koala:','🐩'=>':poodle:','🐫'=>':camel:','🐬'=>':dolphin:','🐭'=>':mouse:','🐮'=>':cow:','🐯'=>':tiger:','🐰'=>':rabbit:','🐱'=>':cat:','🐲'=>':dragon_face:','🐳'=>':whale:','🐴'=>':horse:','🐵'=>':monkey_face:','🐶'=>':dog:','🐷'=>':pig:','🐸'=>':frog:','🐹'=>':hamster:','🐺'=>':wolf:','🐻'=>':bear:','🐼'=>':panda_face:','🐽'=>':pig_nose:','🐾'=>':feet:','👀'=>':eyes:','👂'=>':ear:','👃'=>':nose:','👄'=>':lips:','👅'=>':tongue:','👆'=>':point_up_2:','👇'=>':point_down:','👈'=>':point_left:','👉'=>':point_right:','👊'=>':punch:','👋'=>':wave:','👌'=>':ok_hand:','👍'=>':thumbsup:','👎'=>':thumbsdown:','👏'=>':clap:','👐'=>':open_hands:','👑'=>':crown:','👒'=>':womans_hat:','👓'=>':eyeglasses:','👔'=>':necktie:','👕'=>':shirt:','👖'=>':jeans:','👗'=>':dress:','👘'=>':kimono:','👙'=>':bikini:','👚'=>':womans_clothes:','👛'=>':purse:','👜'=>':handbag:','👝'=>':pouch:','👞'=>':mans_shoe:','👟'=>':athletic_shoe:','👠'=>':high_heel:','👡'=>':sandal:','👢'=>':boot:','👣'=>':footprints:','👤'=>':bust_in_silhouette:','👦'=>':boy:','👧'=>':girl:','👨'=>':man:','👩'=>':woman:','👪'=>':family:','👫'=>':couple:','👮'=>':cop:','👯'=>':dancers:','👰'=>':bride_with_veil:','👱'=>':person_with_blond_hair:','👲'=>':man_with_gua_pi_mao:','👳'=>':man_with_turban:','👴'=>':older_man:','👵'=>':older_woman:','👶'=>':baby:','👷'=>':construction_worker:','👸'=>':princess:','👹'=>':japanese_ogre:','👺'=>':japanese_goblin:','👻'=>':ghost:','👼'=>':angel:','👽'=>':alien:','👾'=>':space_invader:','👿'=>':imp:','💀'=>':skull:','📇'=>':card_index:','💁'=>':information_desk_person:','💂'=>':guardsman:','💃'=>':dancer:','💄'=>':lipstick:','💅'=>':nail_care:','📒'=>':ledger:','💆'=>':massage:','📓'=>':notebook:','💇'=>':haircut:','📔'=>':notebook_with_decorative_cover:','💈'=>':barber:','📕'=>':closed_book:','💉'=>':syringe:','📖'=>':book:','💊'=>':pill:','📗'=>':green_book:','💋'=>':kiss:','📘'=>':blue_book:','💌'=>':love_letter:','📙'=>':orange_book:','💍'=>':ring:','📚'=>':books:','💎'=>':gem:','📛'=>':name_badge:','💏'=>':couplekiss:','📜'=>':scroll:','💐'=>':bouquet:','📝'=>':pencil:','💑'=>':couple_with_heart:','📞'=>':telephone_receiver:','💒'=>':wedding:','📟'=>':pager:','📠'=>':fax:','💓'=>':heartbeat:','📡'=>':satellite:','📢'=>':loudspeaker:','💔'=>':broken_heart:','📣'=>':mega:','📤'=>':outbox_tray:','💕'=>':two_hearts:','📥'=>':inbox_tray:','📦'=>':package:','💖'=>':sparkling_heart:','📧'=>':e-mail:','📨'=>':incoming_envelope:','💗'=>':heartpulse:','📩'=>':envelope_with_arrow:','📪'=>':mailbox_closed:','💘'=>':cupid:','📫'=>':mailbox:','📮'=>':postbox:','💙'=>':blue_heart:','📰'=>':newspaper:','📱'=>':iphone:','💚'=>':green_heart:','📲'=>':calling:','📳'=>':vibration_mode:','💛'=>':yellow_heart:','📴'=>':mobile_phone_off:','📶'=>':signal_strength:','💜'=>':purple_heart:','📷'=>':camera:','📹'=>':video_camera:','💝'=>':gift_heart:','📺'=>':tv:','📻'=>':radio:','💞'=>':revolving_hearts:','📼'=>':vhs:','🔃'=>':arrows_clockwise:','💟'=>':heart_decoration:','🔊'=>':loud_sound:','🔋'=>':battery:','💠'=>':diamond_shape_with_a_dot_inside:','🔌'=>':electric_plug:','🔍'=>':mag:','💡'=>':bulb:','🔎'=>':mag_right:','🔏'=>':lock_with_ink_pen:','💢'=>':anger:','🔐'=>':closed_lock_with_key:','🔑'=>':key:','💣'=>':bomb:','🔒'=>':lock:','🔓'=>':unlock:','💤'=>':zzz:','🔔'=>':bell:','🔖'=>':bookmark:','💥'=>':boom:','🔗'=>':link:','🔘'=>':radio_button:','💦'=>':sweat_drops:','🔙'=>':back:','🔚'=>':end:','💧'=>':droplet:','🔛'=>':on:','🔜'=>':soon:','💨'=>':dash:','🔝'=>':top:','🔞'=>':underage:','💩'=>':poop:','🔟'=>':keycap_ten:','💪'=>':muscle:','🔠'=>':capital_abcd:','🔡'=>':abcd:','💫'=>':dizzy:','🔢'=>':1234:','🔣'=>':symbols:','💬'=>':speech_balloon:','🔤'=>':abc:','🔥'=>':fire:','💮'=>':white_flower:','🔦'=>':flashlight:','🔧'=>':wrench:','💯'=>':100:','🔨'=>':hammer:','🔩'=>':nut_and_bolt:','💰'=>':moneybag:','🔪'=>':knife:','🔫'=>':gun:','💱'=>':currency_exchange:','🔮'=>':crystal_ball:','💲'=>':heavy_dollar_sign:','🔯'=>':six_pointed_star:','💳'=>':credit_card:','🔰'=>':beginner:','🔱'=>':trident:','💴'=>':yen:','🔲'=>':black_square_button:','🔳'=>':white_square_button:','💵'=>':dollar:','🔴'=>':red_circle:','🔵'=>':large_blue_circle:','💸'=>':money_with_wings:','🔶'=>':large_orange_diamond:','🔷'=>':large_blue_diamond:','💹'=>':chart:','🔸'=>':small_orange_diamond:','🔹'=>':small_blue_diamond:','💺'=>':seat:','🔺'=>':small_red_triangle:','🔻'=>':small_red_triangle_down:','💻'=>':computer:','🔼'=>':arrow_up_small:','💼'=>':briefcase:','🔽'=>':arrow_down_small:','🕐'=>':clock1:','💽'=>':minidisc:','🕑'=>':clock2:','💾'=>':floppy_disk:','🕒'=>':clock3:','💿'=>':cd:','🕓'=>':clock4:','📀'=>':dvd:','🕔'=>':clock5:','🕕'=>':clock6:','📁'=>':file_folder:','🕖'=>':clock7:','🕗'=>':clock8:','📂'=>':open_file_folder:','🕘'=>':clock9:','🕙'=>':clock10:','📃'=>':page_with_curl:','🕚'=>':clock11:','🕛'=>':clock12:','📄'=>':page_facing_up:','🗻'=>':mount_fuji:','🗼'=>':tokyo_tower:','📅'=>':date:','🗽'=>':statue_of_liberty:','🗾'=>':japan:','📆'=>':calendar:','🗿'=>':moyai:','😁'=>':grin:','😂'=>':joy:','😃'=>':smiley:','📈'=>':chart_with_upwards_trend:','😄'=>':smile:','😅'=>':sweat_smile:','📉'=>':chart_with_downwards_trend:','😆'=>':laughing:','😉'=>':wink:','📊'=>':bar_chart:','😊'=>':blush:','😋'=>':yum:','📋'=>':clipboard:','😌'=>':relieved:','😍'=>':heart_eyes:','📌'=>':pushpin:','😏'=>':smirk:','😒'=>':unamused:','📍'=>':round_pushpin:','😓'=>':sweat:','😔'=>':pensive:','📎'=>':paperclip:','😖'=>':confounded:','😘'=>':kissing_heart:','📏'=>':straight_ruler:','😚'=>':kissing_closed_eyes:','😜'=>':stuck_out_tongue_winking_eye:','📐'=>':triangular_ruler:','😝'=>':stuck_out_tongue_closed_eyes:','😞'=>':disappointed:','📑'=>':bookmark_tabs:','😠'=>':angry:','😡'=>':rage:','😢'=>':cry:','😣'=>':persevere:','😤'=>':triumph:','😥'=>':disappointed_relieved:','😨'=>':fearful:','😩'=>':weary:','😪'=>':sleepy:','😫'=>':tired_face:','😭'=>':sob:','😰'=>':cold_sweat:','😱'=>':scream:','😲'=>':astonished:','😳'=>':flushed:','😵'=>':dizzy_face:','😷'=>':mask:','😸'=>':smile_cat:','😹'=>':joy_cat:','😺'=>':smiley_cat:','😻'=>':heart_eyes_cat:','😼'=>':smirk_cat:','😽'=>':kissing_cat:','😾'=>':pouting_cat:','😿'=>':crying_cat_face:','🙀'=>':scream_cat:','🙅'=>':no_good:','🙆'=>':ok_woman:','🙇'=>':bow:','🙈'=>':see_no_evil:','🙉'=>':hear_no_evil:','🙊'=>':speak_no_evil:','🙋'=>':raising_hand:','🙌'=>':raised_hands:','🙍'=>':person_frowning:','🙎'=>':person_with_pouting_face:','🙏'=>':pray:','🚀'=>':rocket:','🚃'=>':railway_car:','🚄'=>':bullettrain_side:','🚅'=>':bullettrain_front:','🚇'=>':metro:','🚉'=>':station:','🚌'=>':bus:','🚏'=>':busstop:','🚑'=>':ambulance:','🚒'=>':fire_engine:','🚓'=>':police_car:','🚕'=>':taxi:','🚗'=>':red_car:','🚙'=>':blue_car:','🚚'=>':truck:','🚢'=>':ship:','🚤'=>':speedboat:','🚥'=>':traffic_light:','🚧'=>':construction:','🚨'=>':rotating_light:','🚩'=>':triangular_flag_on_post:','🚪'=>':door:','🚫'=>':no_entry_sign:','🚬'=>':smoking:','🚭'=>':no_smoking:','🚲'=>':bike:','🚶'=>':walking:','🚹'=>':mens:','🚺'=>':womens:','🚻'=>':restroom:','🚼'=>':baby_symbol:','🚽'=>':toilet:','🚾'=>':wc:','🛀'=>':bath:','😀'=>':grinning:','😇'=>':innocent:','😈'=>':smiling_imp:','😎'=>':sunglasses:','😐'=>':neutral_face:','😑'=>':expressionless:','😕'=>':confused:','😗'=>':kissing:','😙'=>':kissing_smiling_eyes:','😛'=>':stuck_out_tongue:','😟'=>':worried:','😦'=>':frowning:','😧'=>':anguished:','😬'=>':grimacing:','😮'=>':open_mouth:','😯'=>':hushed:','😴'=>':sleeping:','😶'=>':no_mouth:','🚁'=>':helicopter:','🚂'=>':steam_locomotive:','🚆'=>':train2:','🚈'=>':light_rail:','🚊'=>':tram:','🚍'=>':oncoming_bus:','🚎'=>':trolleybus:','🚐'=>':minibus:','🚔'=>':oncoming_police_car:','🚖'=>':oncoming_taxi:','🚘'=>':oncoming_automobile:','🚛'=>':articulated_lorry:','🚜'=>':tractor:','🚝'=>':monorail:','🚞'=>':mountain_railway:','🚟'=>':suspension_railway:','🚠'=>':mountain_cableway:','🚡'=>':aerial_tramway:','🚣'=>':rowboat:','🚦'=>':vertical_traffic_light:','🚮'=>':put_litter_in_its_place:','🚯'=>':do_not_litter:','🚰'=>':potable_water:','🚱'=>':non-potable_water:','🚳'=>':no_bicycles:','🚴'=>':bicyclist:','🚵'=>':mountain_bicyclist:','🚷'=>':no_pedestrians:','🚸'=>':children_crossing:','🚿'=>':shower:','🛁'=>':bathtub:','🛂'=>':passport_control:','🛃'=>':customs:','🛄'=>':baggage_claim:','🛅'=>':left_luggage:','🌍'=>':earth_africa:','🌎'=>':earth_americas:','🌐'=>':globe_with_meridians:','🌒'=>':waxing_crescent_moon:','🌖'=>':waning_gibbous_moon:','🌗'=>':last_quarter_moon:','🌘'=>':waning_crescent_moon:','🌚'=>':new_moon_with_face:','🌜'=>':last_quarter_moon_with_face:','🌝'=>':full_moon_with_face:','🌞'=>':sun_with_face:','🌲'=>':evergreen_tree:','🌳'=>':deciduous_tree:','🍋'=>':lemon:','🍐'=>':pear:','🍼'=>':baby_bottle:','🏇'=>':horse_racing:','🏉'=>':rugby_football:','🏤'=>':european_post_office:','🐀'=>':rat:','🐁'=>':mouse2:','🐂'=>':ox:','🐃'=>':water_buffalo:','🐄'=>':cow2:','🐅'=>':tiger2:','🐆'=>':leopard:','🐇'=>':rabbit2:','🐈'=>':cat2:','🐉'=>':dragon:','🐊'=>':crocodile:','🐋'=>':whale2:','🐏'=>':ram:','🐐'=>':goat:','🐓'=>':rooster:','🐕'=>':dog2:','🐖'=>':pig2:','🐪'=>':dromedary_camel:','👥'=>':busts_in_silhouette:','👬'=>':two_men_holding_hands:','👭'=>':two_women_holding_hands:','💭'=>':thought_balloon:','💶'=>':euro:','💷'=>':pound:','📬'=>':mailbox_with_mail:','📭'=>':mailbox_with_no_mail:','📯'=>':postal_horn:','📵'=>':no_mobile_phones:','🔀'=>':twisted_rightwards_arrows:','🔁'=>':repeat:','🔂'=>':repeat_one:','🔄'=>':arrows_counterclockwise:','🔅'=>':low_brightness:','🔆'=>':high_brightness:','🔇'=>':mute:','🔉'=>':sound:','🔕'=>':no_bell:','🔬'=>':microscope:','🔭'=>':telescope:','🕜'=>':clock130:','🕝'=>':clock230:','🕞'=>':clock330:','🕟'=>':clock430:','🕠'=>':clock530:','🕡'=>':clock630:','🕢'=>':clock730:','🕣'=>':clock830:','🕤'=>':clock930:','🕥'=>':clock1030:','🕦'=>':clock1130:','🕧'=>':clock1230:','🔈'=>':speaker:','🚋'=>':train:','➿'=>':loop:','🇦🇫'=>':af:','🇦🇱'=>':al:','🇩🇿'=>':dz:','🇦🇩'=>':ad:','🇦🇴'=>':ao:','🇦🇬'=>':ag:','🇦🇷'=>':ar:','🇦🇲'=>':am:','🇦🇺'=>':au:','🇦🇹'=>':at:','🇦🇿'=>':az:','🇧🇸'=>':bs:','🇧🇭'=>':bh:','🇧🇩'=>':bd:','🇧🇧'=>':bb:','🇧🇾'=>':by:','🇧🇪'=>':be:','🇧🇿'=>':bz:','🇧🇯'=>':bj:','🇧🇹'=>':bt:','🇧🇴'=>':bo:','🇧🇦'=>':ba:','🇧🇼'=>':bw:','🇧🇷'=>':br:','🇧🇳'=>':bn:','🇧🇬'=>':bg:','🇧🇫'=>':bf:','🇧🇮'=>':bi:','🇰🇭'=>':kh:','🇨🇲'=>':cm:','🇨🇦'=>':ca:','🇨🇻'=>':cv:','🇨🇫'=>':cf:','🇹🇩'=>':td:','🇨🇱'=>':chile:','🇨🇴'=>':co:','🇰🇲'=>':km:','🇨🇷'=>':cr:','🇨🇮'=>':ci:','🇭🇷'=>':hr:','🇨🇺'=>':cu:','🇨🇾'=>':cy:','🇨🇿'=>':cz:','🇨🇩'=>':congo:','🇩🇰'=>':dk:','🇩🇯'=>':dj:','🇩🇲'=>':dm:','🇩🇴'=>':do:','🇹🇱'=>':tl:','🇪🇨'=>':ec:','🇪🇬'=>':eg:','🇸🇻'=>':sv:','🇬🇶'=>':gq:','🇪🇷'=>':er:','🇪🇪'=>':ee:','🇪🇹'=>':et:','🇫🇯'=>':fj:','🇫🇮'=>':fi:','🇬🇦'=>':ga:','🇬🇲'=>':gm:','🇬🇪'=>':ge:','🇬🇭'=>':gh:','🇬🇷'=>':gr:','🇬🇩'=>':gd:','🇬🇹'=>':gt:','🇬🇳'=>':gn:','🇬🇼'=>':gw:','🇬🇾'=>':gy:','🇭🇹'=>':ht:','🇭🇳'=>':hn:','🇭🇺'=>':hu:','🇮🇸'=>':is:','🇮🇳'=>':in:','🇮🇩'=>':indonesia:','🇮🇷'=>':ir:','🇮🇶'=>':iq:','🇮🇪'=>':ie:','🇮🇱'=>':il:','🇯🇲'=>':jm:','🇯🇴'=>':jo:','🇰🇿'=>':kz:','🇰🇪'=>':ke:','🇰🇮'=>':ki:','🇽🇰'=>':xk:','🇰🇼'=>':kw:','🇰🇬'=>':kg:','🇱🇦'=>':la:','🇱🇻'=>':lv:','🇱🇧'=>':lb:','🇱🇸'=>':ls:','🇱🇷'=>':lr:','🇱🇾'=>':ly:','🇱🇮'=>':li:','🇱🇹'=>':lt:','🇱🇺'=>':lu:','🇲🇰'=>':mk:','🇲🇬'=>':mg:','🇲🇼'=>':mw:','🇲🇾'=>':my:','🇲🇻'=>':mv:','🇲🇱'=>':ml:','🇲🇹'=>':mt:','🇲🇭'=>':mh:','🇲🇷'=>':mr:','🇲🇺'=>':mu:','🇲🇽'=>':mx:','🇫🇲'=>':fm:','🇲🇩'=>':md:','🇲🇨'=>':mc:','🇲🇳'=>':mn:','🇲🇪'=>':me:','🇲🇦'=>':ma:','🇲🇿'=>':mz:','🇲🇲'=>':mm:','🇳🇦'=>':na:','🇳🇷'=>':nr:','🇳🇵'=>':np:','🇳🇱'=>':nl:','🇳🇿'=>':nz:','🇳🇮'=>':ni:','🇳🇪'=>':ne:','🇳🇬'=>':nigeria:','🇰🇵'=>':kp:','🇳🇴'=>':no:','🇴🇲'=>':om:','🇵🇰'=>':pk:','🇵🇼'=>':pw:','🇵🇦'=>':pa:','🇵🇬'=>':pg:','🇵🇾'=>':py:','🇵🇪'=>':pe:','🇵🇭'=>':ph:','🇵🇱'=>':pl:','🇵🇹'=>':pt:','🇶🇦'=>':qa:','🇹🇼'=>':tw:','🇨🇬'=>':cg:','🇷🇴'=>':ro:','🇷🇼'=>':rw:','🇰🇳'=>':kn:','🇱🇨'=>':lc:','🇻🇨'=>':vc:','🇼🇸'=>':ws:','🇸🇲'=>':sm:','🇸🇹'=>':st:','🇸🇦'=>':saudiarabia:','🇸🇳'=>':sn:','🇷🇸'=>':rs:','🇸🇨'=>':sc:','🇸🇱'=>':sl:','🇸🇬'=>':sg:','🇸🇰'=>':sk:','🇸🇮'=>':si:','🇸🇧'=>':sb:','🇸🇴'=>':so:','🇿🇦'=>':za:','🇱🇰'=>':lk:','🇸🇩'=>':sd:','🇸🇷'=>':sr:','🇸🇿'=>':sz:','🇸🇪'=>':se:','🇨🇭'=>':ch:','🇸🇾'=>':sy:','🇹🇯'=>':tj:','🇹🇿'=>':tz:','🇹🇭'=>':th:','🇹🇬'=>':tg:','🇹🇴'=>':to:','🇹🇹'=>':tt:','🇹🇳'=>':tn:','🇹🇷'=>':tr:','🇹🇲'=>':turkmenistan:','🇹🇻'=>':tuvalu:','🇺🇬'=>':ug:','🇺🇦'=>':ua:','🇦🇪'=>':ae:','🇺🇾'=>':uy:','🇺🇿'=>':uz:','🇻🇺'=>':vu:','🇻🇦'=>':va:','🇻🇪'=>':ve:','🇻🇳'=>':vn:','🇪🇭'=>':eh:','🇾🇪'=>':ye:','🇿🇲'=>':zm:','🇿🇼'=>':zw:','🇵🇷'=>':pr:','🇰🇾'=>':ky:','🇧🇲'=>':bm:','🇵🇫'=>':pf:','🇵🇸'=>':ps:','🇳🇨'=>':nc:','🇸🇭'=>':sh:','🇦🇼'=>':aw:','🇻🇮'=>':vi:','🇭🇰'=>':hk:','🇦🇨'=>':ac:','🇲🇸'=>':ms:','🇬🇺'=>':gu:','🇬🇱'=>':gl:','🇳🇺'=>':nu:','🇼🇫'=>':wf:','🇲🇴'=>':mo:','🇫🇴'=>':fo:','🇫🇰'=>':fk:','🇯🇪'=>':je:','🇦🇮'=>':ai:','🇬🇮'=>':gi:');

	public static function toShortName($text)
	{
		$regexp = '((?>(?>[#0-9](?>\\xEF\\xB8\\x8F)?\\xE2\\x83\\xA3|\\xC2[\\xA9\\xAE]|\\xE2(?>\\x80\\xBC|[\\x81-\\xAD].)|\\xE3(?>\\x80[\\xB0\\xBD]|\\x8A[\\x97\\x99])|\\xF0\\x9F(?>[\\x80-\\x86].|\\x87.\\xF0\\x9F\\x87.|[\\x88-\\x9B].))(?>\\xEF\\xB8\\x8F)?))S';

		return \preg_replace_callback($regexp, __CLASS__ . '::toShortNameCallback', $text);
	}

	protected static function toShortNameCallback($m)
	{
		return (isset(self::$map[$m[0]])) ? self::$map[$m[0]] : $m[0];
	}
}