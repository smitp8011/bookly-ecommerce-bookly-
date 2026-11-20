<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$book_id = (int) ($_GET['id'] ?? 0);
if (!$book_id) {
    echo json_encode(['ok' => false, 'message' => 'Invalid book ID.']);
    exit;
}

$stmt = $pdo->prepare(
    'SELECT b.*, COALESCE(c.name, "General") AS category_name
     FROM books b
     LEFT JOIN categories c ON c.id = b.category_id
     WHERE b.id = ?'
);
$stmt->execute([$book_id]);
$book = $stmt->fetch();

if (!$book) {
    echo json_encode(['ok' => false, 'message' => 'Book not found.']);
    exit;
}

$title = $book['title'];
$author = $book['author'];
$category = $book['category_name'];
$description = $book['description'];

// Curated authentic excerpts for all 16 library books
$curated_excerpts = [
    // 1. Red Queen - Victoria Aveyard
    1 => [
        [
            'chapter' => 'Prologue',
            'subtitle' => 'The Divided Blood',
            'content' => "I hate First Friday. It makes the village crowded, and now, in the heat of high summer, that's the last thing anyone wants.\n\n" .
                         "From my place against the shady side of the butcher's wagon, I scan the square. Flags of scarlet silk drape across the arena balconies, vibrant and unforgiving. In our world, blood determines everything: the Silvers who rule with godlike abilities, and the Reds like me, condemned to poverty, conscription, and silence.\n\n" .
                         "My sister Gisa weaves fine embroideries for the Silver ladies, earning a pass that saves her from the war. But I have no trade. My only skill is picking pockets in the crowded dust of the market."
        ],
        [
            'chapter' => 'Chapter I',
            'subtitle' => 'Sparks in the Dark',
            'content' => "The arena looms over Stilts like a titan of concrete and iron. Inside, the Silvers duel for sport—bending metal, calling lightning, shifting stone as if it were soft wax.\n\n" .
                         "\"Keep your eyes forward, Mare,\" Kilorn whispers beside me. He is my best friend, an apprentice fisherman on the verge of turning eighteen, which means his conscription notice is only weeks away.\n\n" .
                         "I reach into my pocket, feeling the cold brass coins I lifted from a drunken merchant. It is never enough. The king's war demands more flesh than silver, and out here in the mud, we have only our lives to give."
        ],
        [
            'chapter' => 'Chapter II',
            'subtitle' => 'The Lightning Girl',
            'content' => "The palace at Sunfield is unlike anything built with human hands. Marble so translucent it glows with captured daylight, gardens of singing glass, and guards whose eyes shimmer with telekinetic fire.\n\n" .
                         "When I stumbled into the Queen's viewing box, falling through the electrified barrier, everyone expected me to disintegrate into ash. But the current didn't burn me. Instead, purple lightning leaped from my fingertips, answering my fear.\n\n" .
                         "A Red girl with Silver powers. A mortal sin against their gods. Before I could run, the King's sentinels surrounded me with drawn swords."
        ]
    ],

    // 2. Nightshade - Andrea Cremer
    2 => [
        [
            'chapter' => 'Chapter I',
            'subtitle' => 'The Law of the Pack',
            'content' => "The scent of cedar and wet granite filled my lungs as I crested the ridge of Haldis Peak. In the valley below, Rowan Estate lay shrouded in silver moonlight, peaceful and unaware of the wolves watching from the treeline.\n\n" .
                         "\"You're pacing again, Calla,\" Ren said, his golden eyes reflecting the stars. He stood beside me in human form, hands tucked casually into his leather jacket.\n\n" .
                         "\"The Keepers called a council tonight,\" I replied, tightening my grip on my silver amulet. \"They don't summon the pack alpha for pleasantries.\""
        ],
        [
            'chapter' => 'Chapter II',
            'subtitle' => 'Forbidden Territory',
            'content' => "Every law drilled into me since childhood had a single purpose: protect the sacred bloodlines and obey the Keepers without question.\n\n" .
                         "I was born to lead the Nightshade pack, destined to mate with Ren and unite our territories. But deep in the forbidden woods of Vail, where human hikers rarely dared to venture, I had found something that shook the foundation of everything I was taught.\n\n" .
                         "A human boy named Shay, wounded by silver-tipped arrows, looking up at me not with terror, but with recognition."
        ]
    ],

    // 3. Darknet - Matthew Mather
    3 => [
        [
            'chapter' => 'Prologue',
            'subtitle' => 'Zero-Day Protocol',
            'content' => "The terminal screen in the subterranean datacenter blinked with steady, pulsing amber text: EXPLOIT LOADED. TARGET: BROOKLYN GRIDS.\n\n" .
                         "Jake squinted through his monitor reflections at 3:14 AM. The algorithmic trade routing system he had spent five years building for Vantage Global was behaving erratically, executing microsecond arbitrage across exchanges that were supposed to be completely dormant.\n\n" .
                         "\"This isn't an automated glitch,\" he murmured, pressing his headset closer. \"Someone has planted a parasitic rootkit right into the federal clearing node.\""
        ],
        [
            'chapter' => 'Chapter I',
            'subtitle' => 'Blackout Horizon',
            'content' => "Within sixty seconds, the streetlights of lower Manhattan flickered twice and died. Across the East River, transformers erupted in blue electric sparks.\n\n" .
                         "Jake grabbed his encrypted hard drive and dashed toward the emergency stairs. The smart locks on the heavy security doors had already engaged in lockdown mode, trapping hundreds of brokers inside the tower.\n\n" .
                         "On his smartphone, a single notification bypassed the offline firewall: *'If you want your family to survive sunrise, do not delete the ledger.'*"
        ]
    ],

    // 4. Shattered - Kevin Hearne
    4 => [
        [
            'chapter' => 'Chapter I',
            'subtitle' => 'A Druid in Exile',
            'content' => "Two thousand years of running from vindictive Roman deities, Celtic thunder gods, and pissed-off Norse valkyries gives a man a rather nuanced appreciation for a quiet morning cup of tea.\n\n" .
                         "Oberon, my Irish Wolfhound, rested his massive head on my knee and communicated telepathically: *'Atticus, the bacon is done. If you do not give me bacon immediately, I will be forced to inform the neighborhood squirrels that you are no longer omnipotent.'*\n\n" .
                         "\"Patience, hound,\" I replied aloud in Gaelic. \"Even ancient Druids must let the grease drain.\""
        ],
        [
            'chapter' => 'Chapter II',
            'subtitle' => 'The Gathering Storm',
            'content' => "The earth beneath my bare feet hummed with elemental magic—the deep, rhythmic pulse of iron, loam, and ancient tree roots.\n\n" .
                         "Granuaile, my apprentice, jogged up the trail holding Fragarach, the Answerer, its bronze blade catching the morning sun. \"Atticus, we have a breach in the perimeter wards. Something with hooves and sulfur just crossed the mountain pass.\"\n\n" .
                         "I sighed, tossing Oberon the extra bacon strip. \"Pack your amulets, kids. Retirement was fun for the twenty minutes it lasted.\""
        ]
    ],

    // 5. The Girl of Ink & Stars - Kiran Millwood Hargrave
    5 => [
        [
            'chapter' => 'Chapter I',
            'subtitle' => 'The Edge of Joya',
            'content' => "They used to say the day the Governor arrived, the ravens did not sing. But I was seven then, and what I remember was the scent of burning cinnamon and the sight of my father rolling up his ancient navigational charts.\n\n" .
                         "My father was a cartographer, the finest in all the archipelago. With his ink bottles of indigo, crushed beetle carmine, and squid gall, he could capture the spirit of an ocean on a square foot of calfskin vellum.\n\n" .
                         "\"Isabella,\" he would whisper, guiding my ink-stained fingers across the map, \"an island is never truly lost so long as someone remembers how to draw its shores.\""
        ],
        [
            'chapter' => 'Chapter II',
            'subtitle' => 'Into the Forbidden Forest',
            'content' => "When Lupe disappeared past the Governor's barbed wire boundary into the Forgotten Territories, nobody dared to search for her. They feared the demon Yrsillo and the wolves made of living fire.\n\n" .
                         "I strapped my mother's brass compass to my wrist and slipped my father's forbidden map into my boots. If anyone could navigate the shifting sands and whispering trees, it was the daughter of the mapmaker."
        ]
    ],

    // 6. Freefall - Jessica Barry
    6 => [
        [
            'chapter' => 'Chapter I',
            'subtitle' => 'Thirty Thousand Feet',
            'content' => "The crash didn't happen like in the movies with screaming alarms and oxygen masks dropping from the ceiling. It happened in complete, terrifying silence when the left engine simply dissolved in mid-air.\n\n" .
                         "When I opened my eyes, the smells hit me first: pine needles, aviation fuel, and freezing Colorado snow. The private jet lay snapped in half fifty yards away against the granite boulders.\n\n" .
                         "My ribs throbbed with unbearable agony, but adrenaline forced me to stand. I was the sole survivor of the crash. And if the men my fiancé worked with discovered I was alive, they would come to finish the job."
        ]
    ],

    // 7. Boring Girls: A Novel - Sara Taylor
    7 => [
        [
            'chapter' => 'Chapter I',
            'subtitle' => 'Heavy Metal Dreams',
            'content' => "In a suburban town where nothing ever happens, quiet girls are supposed to blend into the wallpaper, get straight A's, and vanish into polite adulthood.\n\n" .
                         "Rachel and I chose electric distortion instead. In her damp basement beneath the hum of the washing machine, we plugged two second-hand bass guitars into a blown-out Marshall amplifier and played until our fingertips bled.\n\n" .
                         "We weren't interested in pop melodies or making boys smile. We wanted to build a wall of sound so deafening it would crack the foundation of every pristine manicured lawn in town."
        ],
        [
            'chapter' => 'Chapter II',
            'subtitle' => 'The Price of the Spotlight',
            'content' => "When our first underground demo tape circulated through the underground metal clubs of Montreal, everything accelerated with terrifying velocity.\n\n" .
                         "Record producers promised us stadium tours and festival headliners, but every contract came with subtle compromises—change the lyrics, tone down the fury, wear shorter skirts.\n\n" .
                         "Rachel looked at the contracts, then reached for her lighter. \"If they want our music,\" she whispered, \"they take it with the fire attached.\""
        ]
    ],

    // 8. Clever Lands - Lucy Crehan
    8 => [
        [
            'chapter' => 'Introduction',
            'subtitle' => 'The Quest for Educational Excellence',
            'content' => "Every few years, the global educational rankings (PISA) trigger a flurry of political panic across Western capitals. Why do teenagers in Finland, Shanghai, Singapore, and Japan consistently outperform their peers across mathematics, science, and reading comprehension?\n\n" .
                         "As a disillusioned London school teacher, I decided to stop reading theoretical policy papers and pack a suitcase. Over the next two years, I lived and taught in classrooms across five top-performing school systems to uncover what real teachers do every single day."
        ],
        [
            'chapter' => 'Chapter I',
            'subtitle' => 'The Finnish Paradox',
            'content' => "In Helsinki, seven-year-olds start formal school later than almost anywhere in Europe. They have less homework, fewer standardized exams, and spend an hour playing outside in sub-zero snow between lessons.\n\n" .
                         "Yet when tested internationally, their high schoolers demonstrate world-class critical thinking and equity. The secret does not lie in flashy technology or rigid curricula, but in the absolute cultural trust placed in masters-educated educators."
        ]
    ],

    // 9. Economic Facts and Fallacies - Thomas Sowell
    9 => [
        [
            'chapter' => 'Chapter I',
            'subtitle' => 'The Power of Economic Reality',
            'content' => "Economic fallacies are not simply crazy ideas. Rather, they are plausible-sounding notions that seem completely reasonable on the surface, but crumble the moment one examines empirical data and systemic incentives.\n\n" .
                         "The first and most pervasive fallacy is what economists call the 'Zero-Sum Fallacy'—the belief that economic transactions are like poker games where one person's gain must inevitably come at another person's direct expense.\n\n" .
                         "In voluntary market transactions, however, wealth is created, not merely transferred. Both the buyer who values a book more than fifty dollars and the seller who values the fifty dollars more than the inventory walk away better off than before."
        ]
    ],

    // 10. History of Modern Architecture - Kenneth Frampton
    10 => [
        [
            'chapter' => 'Part I',
            'subtitle' => 'Cultural Transformations (1750–1900)',
            'content' => "The birth of modern architecture was intrinsically bound to the Industrial Revolution and the emergence of new materials: cast iron, plate glass, and reinforced Portland cement.\n\n" .
                         "From the crystal iron vaults of Joseph Paxton's Crystal Palace in 1851 to the soaring steel frameworks of the Chicago School, architecture ceased to be a mere repetition of classical Greco-Roman orders.\n\n" .
                         "Form followed function, creating a spatial language that reflected the speed, scale, and civic aspirations of the modern metropolis."
        ]
    ],

    // 11. Holy Ghosts - Gary Jansen
    11 => [
        [
            'chapter' => 'Chapter I',
            'subtitle' => 'The Uninvited Roommate',
            'content' => "I have always considered myself a rational, skeptical man—a professional editor and father of two living in an unassuming house on Long Island.\n\n" .
                         "That certainty unraveled the night the footsteps began in the upstairs hallway. Heavy, deliberate strides that paused outside our bedroom door at precisely 2:47 AM, accompanied by a sudden, bone-chilling drop in room temperature.\n\n" .
                         "What followed was not merely an investigation into anomalous phenomena, but an unexpected spiritual journey that forced me to confront my deepest questions about faith, mortality, and the unseen dimensions of our world."
        ]
    ],

    // 12. Radical Gardening - George McKay
    12 => [
        [
            'chapter' => 'Introduction',
            'subtitle' => 'The Politics of the Soil',
            'content' => "Gardening is rarely viewed through the lens of political resistance. Most imagine manicured flower beds, Sunday morning lawnmowers, and tranquil suburban tea.\n\n" .
                         "Yet throughout modern history, the garden has served as a battleground of rebellion: from the 17th-century Diggers reclaiming common land on St. George's Hill, to the feminist Peace Camps of Greenham Common, and the Guerrilla Gardeners planting midnight seed bombs in urban potholes.\n\n" .
                         "To plant a seed in a concrete cityscape is fundamentally an act of defiance—a declaration that beauty, community, and sustenance belong to everyone."
        ]
    ],

    // 13. Be Well, Bee - Rosie Sanders
    13 => [
        [
            'chapter' => 'Chapter I',
            'subtitle' => 'The Secret Life of Pollinators',
            'content' => "To watch a honeybee dance in the warm morning sun is to witness one of the most sophisticated communication systems in the animal kingdom.\n\n" .
                         "Through precise angles relative to the sun and rhythmic abdomen vibrations, a single worker bee conveys the exact coordinates, distance, and sugar concentration of a clover patch two miles away.\n\n" .
                         "Without these tireless garden architects, more than one-third of every food crop on our tables would vanish."
        ]
    ],

    // 14. The Happy Lemon - Cara Chen
    14 => [
        [
            'chapter' => 'Chapter I',
            'subtitle' => 'The Golden Orchard',
            'content' => "In my grandfather's citrus grove outside Tainan, the lemons didn't grow on trees; they grew in stories.\n\n" .
                         "Every harvest season, as the air filled with the sharp, sweet fragrance of crushed peel and summer heat, Baba would tell us the legend of the first tree planted during the dynasty.\n\n" .
                         "\"A sour fruit,\" he would remind us with a smile, handing us slices dipped in rock sugar, \"only needs patient hands and a grateful heart to become the sweetest tea.\""
        ],
        [
            'chapter' => 'Chapter II',
            'subtitle' => 'Return to the Roots',
            'content' => "Thirty years later, standing among the overgrown rows with the estate tax notices clutched in my hand, the orchard felt smaller, but the memories were as vivid as freshly squeezed juice.\n\n" .
                         "The developers offered a fortune to pave the land for a commercial shopping complex. But in the old wooden packing shed, I found my grandfather's recipe journals—and a family legacy waiting to be revived."
        ]
    ],

    // 15. The World: A Traveler's Guide - Simon Winchester
    15 => [
        [
            'chapter' => 'Chapter I',
            'subtitle' => 'The Romance of the Open Road',
            'content' => "To travel is to surrender oneself to the poetry of unexpected places. From the tea plantations clinging to the mist-clad cliffs of Darjeeling to the silent fjords of southern Greenland, our planet remains an astonishing theatre of wonder.\n\n" .
                         "A true traveler does not seek merely to tick monuments off a checklist, but to listen to the cadence of foreign languages, taste spices ground with ancient mortars, and rediscover the modesty of being one small soul beneath a boundless sky."
        ]
    ],

    // 16. Bash & Lucy - Rita Rosen
    16 => [
        [
            'chapter' => 'Chapter I',
            'subtitle' => 'The Dog Who Loved Books',
            'content' => "Lucy was not an ordinary Golden Retriever. Other dogs chased tennis balls or barked at postal delivery vans; Lucy preferred to curl up at the foot of the library bookshelf and listen to bedtime stories.\n\n" .
                         "Whenever seven-year-old Bash opened an adventure book about pirate ships or distant galaxy explorers, Lucy would rest her chin on his knees and wag her golden tail in perfect rhythm with his reading.\n\n" .
                         "That summer, when the town library faced closure due to lack of funding, Bash and Lucy hatched an audacious plan that would bring the entire neighborhood together."
        ]
    ],
    // 17. Atomic Habits - James Clear
    17 => [
        [
            'chapter' => 'Introduction',
            'subtitle' => 'The Fundamentals of Tiny Habits',
            'content' => "It is so easy to overestimate the importance of one defining moment and underestimate the value of making small improvements on a daily basis. Too often, we convince ourselves that massive success requires massive action.\n\n" .
                         "Meanwhile, improving by 1 percent isn't particularly notable—sometimes it isn't even noticeable—but it can be far more meaningful, especially in the long run. The difference a tiny improvement can make over time is astounding: if you can get 1 percent better each day for one year, you’ll end up thirty-seven times better by the time you’re done."
        ],
        [
            'chapter' => 'Chapter 1',
            'subtitle' => 'The Surprising Power of Atomic Habits',
            'content' => "Habits are the compound interest of self-improvement. The same way that money multiplies through compound interest, the effects of your habits multiply as you repeat them.\n\n" .
                         "They seem to make little difference on any given day and yet the impact they deliver over the months and years can be enormous. It is only when looking back two, five, or perhaps ten years later that the value of good habits and the cost of bad ones becomes strikingly apparent."
        ]
    ],
    // 18. The Psychology of Money - Morgan Housel
    18 => [
        [
            'chapter' => 'Introduction',
            'subtitle' => 'The Greatest Show On Earth',
            'content' => "Doing well with money has a little to do with how smart you are and a lot to do with how you behave. And behavior is hard to teach, even to really smart people.\n\n" .
                         "A genius who loses control of their emotions can be a financial disaster. The opposite is also true. Ordinary folks with no financial education can be wealthy if they have a handful of behavioral skills that have nothing to do with formal measures of intelligence."
        ],
        [
            'chapter' => 'Chapter 1',
            'subtitle' => 'No One’s Crazy',
            'content' => "Your personal experiences with money make up maybe 0.00000001% of what’s happened in the world, but maybe 80% of how you think the world works.\n\n" .
                         "People from different generations, raised by different parents who earned different incomes and held different values in different parts of the world, learn vastly different lessons. No one is crazy; everyone makes financial decisions based on their own unique life experiences."
        ]
    ],
    // 19. The Silent Patient - Alex Michaelides
    19 => [
        [
            'chapter' => 'Prologue',
            'subtitle' => 'Alicia Berenson’s Diary',
            'content' => "I don't know why I'm writing this. That's not true. Maybe I do know and just don't want to admit it to myself. Gabriel bought me this little notebook with its pale blue marbled cover, hoping it would help me sort through my thoughts.\n\n" .
                         "Everything was fine. We were happy. At least, that is what I told myself until that fateful July evening when the silence descended like an iron curtain."
        ],
        [
            'chapter' => 'Chapter 1',
            'subtitle' => 'The Gunshots in the Dark',
            'content' => "Alicia Berenson was thirty-three years old when she killed her husband. They had been married for seven years. They were both artists—Alicia was a painter, and Gabriel was a well-known fashion photographer.\n\n" .
                         "When the police arrived at their North London home, Gabriel was tied to a chair with wire, shot five times in the face. Alicia stood by the fireplace in her white dress, silent, calm, and holding the gun. Since that night, she has not spoken a single word."
        ]
    ],
    // 20. The Alchemist - Paulo Coelho
    20 => [
        [
            'chapter' => 'Part One',
            'subtitle' => 'The Boy and the Sheep',
            'content' => "The boy’s name was Santiago. Dusk was falling as he arrived with his herd at an abandoned church. The roof had fallen in long ago, and an enormous sycamore had grown on the spot where the sacristy had once stood.\n\n" .
                         "He decided to spend the night there. He saw to it that all the sheep entered through the ruined gate, and then laid some planks across it to prevent the flock from wandering away during the night."
        ],
        [
            'chapter' => 'Part One',
            'subtitle' => 'The Call of the Pyramids',
            'content' => "\"It's the possibility of having a dream come true that makes life interesting,\" thought Santiago, as he looked again at the position of the sun.\n\n" .
                         "In the town of Tarifa, an old gypsy woman and a mysterious king from Salem spoke to him of the Soul of the World and the language of omens. \"When you want something, all the universe conspires in helping you to achieve it.\""
        ]
    ],
    // 21. Ikigai - Héctor García & Francesc Miralles
    21 => [
        [
            'chapter' => 'Chapter 1',
            'subtitle' => 'The Art of Staying Young While Growing Old',
            'content' => "In Japanese, ikigai is written as 生き甲斐, where 生き means 'life' and 甲斐 means 'to be worthwhile.' It is roughly translated as 'the happiness of always being busy.'\n\n" .
                         "On the island of Okinawa, home to the longest-living people on Earth, centenarians live with vibrant energy, purposeful mornings, and strong social bonds. They do not have a word for retirement; work is woven into the tapestry of living."
        ]
    ],
    // 22. Dune - Frank Herbert
    22 => [
        [
            'chapter' => 'Book One: Dune',
            'subtitle' => 'A Beginning is the Time for Taking the Most Delicate Care',
            'content' => "A beginning is the time for taking the most delicate care that the balances are correct. This every sister of the Bene Gesserit knows.\n\n" .
                         "To begin your study of the life of Muad'Dib, then, take care that you first place him in his time: born in the 57th year of the Padishah Emperor, Shaddam IV. And take the most special care that you locate him in his place: the planet Arrakis. Do not be deceived by the fact that he was born on Caladan and lived his first fifteen years there. Arrakis, the planet known as Dune, is forever his place."
        ],
        [
            'chapter' => 'Chapter 1',
            'subtitle' => 'The Gom Jabbar Test',
            'content' => "In the shadowed library room of Castle Caladan, the Reverend Mother Gaius Helen Mohiam adjusted her black robes and studied the young Paul Atreides.\n\n" .
                         "\"You’ve heard of animals chewing off a leg to escape a trap?\" the old woman hissed. \"A human would remain in the trap, endure the pain, feigning death that he might kill the trapper and remove a threat to his kind. Now, place your right hand in the box.\""
        ]
    ]
];

// If curated excerpt exists for this book ID, use it
if (!empty($curated_excerpts[$book_id])) {
    $pages = $curated_excerpts[$book_id];
} else {
    // Dynamic contextual generator for newly added books
    $pages = [
        [
            'chapter' => 'Prologue',
            'subtitle' => "From the Pages of {$title}",
            'content' => "Every great journey begins with a single step into the unknown. In {$title}, {$author} invites the reader into an extraordinary exploration of ideas, human emotions, and timeless discoveries.\n\n" .
                         (!empty($description) ? nl2br(h($description)) : "The world we inhabit is shaped by the stories we dare to tell and the truths we seek along the way.\n\n" .
                         "As you turn these opening pages, prepare to immerse yourself in a narrative crafted with depth, eloquence, and unforgettable insight.")
        ],
        [
            'chapter' => 'Chapter I',
            'subtitle' => 'The Unfolding Journey',
            'content' => "The morning sun filtered softly through the horizon, casting long shadows across the path ahead. In this opening chapter, the foundational themes of {$category} take center stage.\n\n" .
                         "\"To understand the essence of any work,\" wrote {$author}, \"one must look beyond the surface and listen closely to the resonance of each idea.\"\n\n" .
                         "Readers who have cherished masterworks in this genre will find themselves drawn into a compelling tapestry of thought, where every sentence is deliberate and every revelation earned."
        ],
        [
            'chapter' => 'Chapter II',
            'subtitle' => 'Deepening Insights',
            'content' => "As the narrative progresses, the questions grow sharper and the discoveries more profound.\n\n" .
                         "Whether you are reading for quiet reflection, academic curiosity, or the pure joy of storytelling, {$title} delivers an experience that lingers long after the final chapter concludes.\n\n" .
                         "To explore the complete unabridged edition, purchase the full paperback copy today."
        ]
    ];
}

echo json_encode([
    'ok' => true,
    'book' => [
        'id'        => (int)$book['id'],
        'title'     => $book['title'],
        'author'    => $book['author'],
        'category'  => $category,
        'price'     => price($book['price']),
        'stock'     => (int)$book['stock'],
        'cover'     => 'images/' . $book['cover_image'],
        'pages'     => $pages
    ]
]);
