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
            <div class="stone-box">
                <div class="lodestone-black" id="lodestone-1"></div>
                <div class="score-box-black">Captured: 
                    <div class="score" id="score-1"></div>
                </div>
            </div>
            <div class="last-move-slot-container">
                <div class="last-move-label-black">Last: </div>
                <div class="last-move-slot-box" id="move-record-black">
                </div>
            </div>
        </div>
        <div id="balance-slider">
            <div class="slider-inner-container-black"></div>
            <div class="slider-inner-container-black"></div>
            <div class="slider-inner-container-black"></div>
            <div class="slider-inner-container-black"></div>
            <div class="slider-inner-container-white"></div>
            <div class="slider-inner-container-white"></div>
            <div class="slider-inner-container-white"></div>
            <div class="slider-inner-container-white"></div>
        </div>
        <div class="user-box-white">
            <div class="pay-btn-container" id="active-white">
                <div class="pay-btn" id="pay-btn-white"></div>
            </div>
            <div class="stone-box">
                <div class="lodestone-white" id="lodestone-2"></div>
                <div class="score-box-white">Captured: 
                    <div class="score" id="score-2"></div>
                </div>
            </div>
            <div class="last-move-slot-container">
                <div class="last-move-label-white">Last: </div>
                <div class="last-move-slot-box" id="move-record-white">
                </div>
            </div>
        </div>
    </div> 
</div>
<div id="dark-shroud">
    <div id="win-lose-draw"></div>
    <div id="pay-message"></div>
</div>

{OVERALL_GAME_FOOTER}