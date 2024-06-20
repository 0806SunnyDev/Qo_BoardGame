{OVERALL_GAME_HEADER}

<div id="main-container">
    <div id="board-frame">
        <div id="board">
            <div id="discs">
            </div>
        </div>
        <div class="h-markers-container">
            <div class="h-markers">1</div>
            <div class="h-markers">2</div>
            <div class="h-markers">3</div>
            <div class="h-markers">4</div>
            <div class="h-markers">5</div>
            <div class="h-markers">6</div>
            <div class="h-markers">7</div>
            <div class="h-markers">8</div>
            <div class="h-markers">9</div>
        </div>
        <div class="v-markers-container">
            <div class="v-markers">A</div>
            <div class="v-markers">B</div>
            <div class="v-markers">C</div>
            <div class="v-markers">D</div>
            <div class="v-markers">E</div>
            <div class="v-markers">F</div>
            <div class="v-markers">G</div>
            <div class="v-markers">H</div>
            <div class="v-markers">I</div>
        </div>
    </div>
    <div class="last-move-display-container">
        <div class="user-box-black">
            <div class="pay-btn-container" id="active-black">
                <div class="pay-btn" id="pay-btn-black"></div>
            </div>
            <div class="stone-box lodestone-black" id="lodestone-1">
            </div>
            <div class="last-move-slot-container">
                <div class="onboard-box-black">On board: <span class="onboard" id="onboard-1"></span></div>
                <div class="ready-box-black">Ready pile: <span class="ready" id="ready-1"></span></div>
                <div class="score-box-black">Captured: <span class="score" id="score-1"></span></div>
            </div>
        </div>
        <div id="balance-slider">
        </div>
        <div class="user-box-white">
            <div class="pay-btn-container" id="active-white">
                <div class="pay-btn" id="pay-btn-white"></div>
            </div>
            <div class="stone-box lodestone-white" id="lodestone-2">
            </div>
            <div class="last-move-slot-container">
                <div class="onboard-box-white">On board: <span class="onboard" id="onboard-2"></span></div>
                <div class="ready-box-white">Ready pile: <span class="ready" id="ready-2"></span></div>
                <div class="score-box-white">Captured: <span class="score" id="score-2"></span></div>
            </div>
        </div>
    </div> 
</div>
<div id="dark-shroud">
    <div id="win-lose-draw"></div>
    <div id="pay-message"></div>
</div>

{OVERALL_GAME_FOOTER}