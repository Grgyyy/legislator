<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Dev Guild</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        window.addEventListener("keyup", (e) => {
            if (e.key.toLowerCase() === "s") {
                setTimeout(() => alert("A wild developer appears... but they immediately vanish into the shadows!"), 500);
            }
        });

        function showStats(id) {
            const allStats = document.querySelectorAll('[id^="dev"]');
            const allHints = document.querySelectorAll('[id^="hint-dev"]');

            allStats.forEach((stat) => {
                if (stat.id !== id) {
                    stat.classList.add("hidden");
                }
            });

            allHints.forEach((hint) => {
                if (hint.id !== "hint-" + id) {
                    hint.classList.remove("hidden");
                }
            });

            const stats = document.getElementById(id);
            const hint = document.getElementById("hint-" + id);

            if (stats.classList.contains("hidden")) {
                stats.classList.remove("hidden");
                hint.classList.add("hidden");
            } else {
                stats.classList.add("hidden");
                hint.classList.remove("hidden");
            }
        }

        window.addEventListener("keydown", (e) => {
            if (e.key.toLowerCase() === "g") {
                setTimeout(() => alert("Dhan casts Git Rebase! History has been rewritten!"), 500);
            }
        });
    </script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Uncial+Antiqua&display=swap');

        .rpg-font {
            font-family: 'Uncial Antiqua', cursive;
            letter-spacing: 1px;
        }

        .scrollbar-hidden::-webkit-scrollbar {
            width: 6px;
        }

        .scrollbar-hidden::-webkit-scrollbar-track {
            background: transparent;
        }

        .scrollbar-hidden::-webkit-scrollbar-thumb {
            background: #1b263e;
            border-radius: 4px;
        }

        .scrollbar-hidden::-webkit-scrollbar-thumb:hover {
            background: #2d3f66;
        }
    </style>
</head>

<body class="bg-gray-900 text-white flex items-center justify-center min-h-screen p-10">
    <div class="w-full max-w-6xl text-center flex flex-col">
        <div class="px-4 py-4">
            <h1 class="text-4xl sm:text-5xl rpg-font text-green-400">⚔️ The Dev Guild ⚔️</h1>
            <p class="text-gray-300 italic mt-2 text-sm sm:text-base">"Only the bravest coders survive the dungeon of deadlines."</p>
        </div>

        <div class="w-full max-w-6xl mt-10 p-2 sm:p-4 text-center">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                <div class="relative p-4 bg-gray-900 border-4 border-blue-500 rounded-lg text-center shadow-lg shadow-blue-500/50 cursor-pointer transform transition-all duration-300 hover:scale-105 hover:shadow-xl hover:shadow-blue-400/50 min-h-[500px] flex flex-col justify-center" onclick="showStats('dev1')">
                    <div class="relative w-24 h-24 mx-auto mb-3">
                        <img src="images/12e1ffc4e4a3e8330b657a92b03cea70924f74c1.jpg" alt="iyan, Archmage of Data" class="w-full h-full rounded-full border-4 border-blue-500 shadow-lg">
                        <div class="absolute inset-0 bg-blue-400 opacity-20 rounded-full animate-pulse"></div>
                    </div>

                    <h2 class="text-3xl rpg-font text-blue-300">iyan</h2>
                    <h3 class="text-2xl rpg-font text-green-300">Archmage of Data & Master of Queries</h3>
                    <p id="hint-dev1" class="text-sm text-gray-400 italic p-2 text-center">Click to unveil his arcane secrets</p>

                    <div id="dev1" class="mt-2 hidden text-left max-h-60 overflow-y-auto scrollbar-hidden">
                        <p class="text-lg text-blue-300 py-2 text-center">"Guardian of the Sacred Database Scrolls"</p>

                        <div class="grid grid-cols-2 gap-4 px-2">
                            <p class="text-sm text-green-200 text-right">Intelligence</p>
                            <p class="text-sm text-yellow-200">120 (Once debugged an issue just by staring at the screen)</p>
                    
                            <p class="text-sm text-green-200 text-right">Strength</p>
                            <p class="text-sm text-yellow-200">95 (Can lift a full database dump, but optimizes it so he never has to!)</p>

                            <p class="text-sm text-green-200 text-right">Spells</p>
                            <p class="text-sm text-yellow-200">Schema Manipulation, <br> Relationship Summoning</p>
                    
                            <p class="text-sm text-green-200 text-right">Passive Buff</p>
                            <p class="text-sm text-yellow-200">Data Enchantment (+50% Query Speed Boost)</p>

                            <p class="text-sm text-green-200 text-right">Commanding Presence</p>
                            <p class="text-sm text-yellow-200">Commands data-driven legions, guiding queries to victory</p>
    
                            <p class="text-sm text-green-200 text-right">Special Skill</p>
                            <p class="text-sm text-yellow-200">Can eat 5 rice in a meal</p>
                        </div>
                    </div>
                </div>

            <div class="relative p-4 bg-gray-900 border-4 border-yellow-500 rounded-lg text-center shadow-lg shadow-yellow-500/50 cursor-pointer transform transition-all duration-300 hover:scale-105 hover:shadow-xl hover:shadow-yellow-400/50 min-h-[500px] flex flex-col justify-center" onclick="showStats('dev2')">
                <div class="relative w-24 h-24 mx-auto mb-3">
                    <img src="images/12e1ffc4e4a3e8330b657a92b03cea70924f74c3.jpg" alt="dan, Arcane Patchweaver & Git Keeper" class="w-full h-full rounded-full border-4 border-yellow-500 shadow-lg">
                    <div class="absolute inset-0 bg-yellow-400 opacity-20 rounded-full animate-pulse"></div>
                </div>

                <h2 class="text-3xl rpg-font text-yellow-300">dan</h2>
                <h3 class="text-2xl rpg-font text-green-300">Arcane Patchweaver & Git Keeper</h3>
                <p id="hint-dev2" class="text-sm text-gray-400 italic p-2 text-center">Click to unveil his arcane secrets</p>

                <div id="dev2" class="mt-2 hidden text-left max-h-60 overflow-y-auto scrollbar-hidden">
                    <p class="text-lg text-blue-300 py-2 text-center">"Fills the Void where Backend Dares Not Tread."</p>
                    
                    <div class="grid grid-cols-2 gap-4 px-2">
                        <p class="text-sm text-green-200 text-right">Code Alchemy</p>
                        <p class="text-sm text-yellow-200">Feature Finisher, <br> Bug Slayer</p>
                
                        <p class="text-sm text-green-200 text-right">Code Discipline</p>
                        <p class="text-sm text-yellow-200">Structure Seeker, <br> Clean Code Fanatic</p>

                        <p class="text-sm text-green-200 text-right">Git Sorcery</p>
                        <p class="text-sm text-yellow-200">Rebases timelines, <br> Rewrites history, <br> Merges Conflicts with Ease</p>

                        <p class="text-sm text-green-200 text-right">Dexterity</p>
                        <p class="text-sm text-yellow-200">99 (Fights for UI/UX with Pixel-perfect Precision)</p>

                        <p class="text-sm text-green-200 text-right">Passive Buff</p>
                        <p class="text-sm text-yellow-200">+100% Readability <br> +95% Maintainability </p>

                        <p class="text-sm text-green-200 text-right">Special Skill</p>
                        <p class="text-sm text-yellow-200"> +∞ Frustration upon sight of spaghetti code</p>
                    </div>
                </div>
            </div>

            <div class="relative p-4 bg-gray-900 border-4 border-blue-500 rounded-lg text-center shadow-lg shadow-blue-500/50 cursor-pointer transform transition-all duration-300 hover:scale-105 hover:shadow-xl hover:shadow-blue-400/50 min-h-[500px] flex flex-col justify-center" onclick="showStats('dev1')">
                <div class="relative w-24 h-24 mx-auto mb-3">
                    <img src="images/12e1ffc4e4a3e8330b657a92b03cea70924f74c2.jpg" alt="ceddie, Guardian of Restricted Scrolls" class="w-full h-full rounded-full border-4 border-blue-500 shadow-lg">
                    <div class="absolute inset-0 bg-blue-400 opacity-20 rounded-full animate-pulse"></div>
                </div>

                <h2 class="text-3xl rpg-font text-blue-300">ceddie</h2>
                <h3 class="text-2xl rpg-font text-green-300 ">Guardian of Restricted Scrolls</h3>
                <p id="hint-dev3" class="text-sm text-gray-400 italic p-2 text-center">Click to unveil his arcane secrets</p>

                <div id="dev3" class="mt-2 hidden text-left max-h-60 overflow-y-auto scrollbar-hidden">
                    <p class="text-lg text-blue-300 py-2 text-center">"Elder Sage of System & Keeper of Exports"</p>

                    <div class="grid grid-cols-2 gap-4 px-2">
                        <p class="text-sm text-green-200 text-right">Legacy Architect</p>
                        <p class="text-sm text-yellow-200">Seasoned developer from the First Era, <br> Architect of the Original LIS</p>
                        
                        <p class="text-sm text-green-200 text-right">Support Sorcerer</p>
                        <p class="text-sm text-yellow-200">Casts Support Spells for SIS & Links with Distant Sages.</p>
                
                        <p class="text-sm text-green-200 text-right">Gatekeeping Arts</p>
                        <p class="text-sm text-yellow-200">Wielder of Roles & Permissions</p>
                
                        <p class="text-sm text-green-200 text-right">Passive Buff</p>
                        <p class="text-sm text-yellow-200">+100% Export Reliability, <br> +85% Access Control Expertise</p>
                
                        <p class="text-sm text-green-200 text-right">Special Skill</p>
                        <p class="text-sm text-yellow-200">Vanishes after completing 8-hour ritual——no overtime spells cast.</p>
                    </div>
                </div>                    
            </div>
        </div>

        <div class="mt-20 py-6 text-center text-gray-400 border-t border-gray-600 w-full">
            <p class="italic text-md pb-3">Special Thanks to:</p>
            <p class="text-blue-300 text-xl">Mr. Joemar Caballero - Guiding Force of the Guild</p>
            <p class="text-blue-300 text-md">Mr. Jotham Hernandez - Overseer of Deployment</p>
        </div>
    </div>
</body>
</html>
