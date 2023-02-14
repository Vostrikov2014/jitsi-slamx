/*
 * Welcome to your app's main JavaScript file!
 *
 */

import {api} from "./jitsiUtils";
import {showPlayPause} from "./moderatorIframe";

class jitsiController {
    api = null;
    displayName = null;
    avatarUrl = null;
    isMuted = false;
    isVideoMuted = false;

    participants = {};

    iframeIsSilent = null;

    constructor(api, displayName, avatarUrl) {
        this.api = api;
        this.displayName = displayName;
        this.avatarUrl = avatarUrl;
        showPlayPause();
        this.initMessengerListener();
    }

    initMessengerListener() {
        const self = this;
        window.addEventListener('message', function (e) {

            const decoded = JSON.parse(e.data);
            if (typeof decoded.scope !== 'undefined' && decoded.scope === "jitsi-admin-iframe") {
                if (decoded.type === 'pauseIframe') {
                    self.pauseConference();
                } else if (decoded.type === 'playIframe') {
                    self.playConference();
                }
            }
        });
    }

    pauseConference() {
        this.iframeIsSilent = true;
        this.api.isAudioMuted().then(muted => {
            this.isMuted = muted;
            if (!muted) {
                this.api.executeCommand('toggleAudio');
            }
        });
        this.api.isVideoMuted().then(muted => {
            this.isVideoMuted = muted;
            if (!muted) {
                this.api.executeCommand('toggleVideo');
            }
        });
        this.api.executeCommand('displayName', '(Away) ' + this.displayName);
        this.api.executeCommand('avatarUrl', 'https://avatars0.githubusercontent.com/u/3671647');
        api.getRoomsInfo().then(rooms => {
            var participants = rooms.rooms[0].participants;
            for (var p of participants) {
                console.log(p);
                api.executeCommand('setParticipantVolume', {
                        participantID: p.id,
                        volume: 0
                    }
                );
            }
        })
    }


    playConference() {
        this.iframeIsSilent = true;
        this.api.executeCommand('displayName', this.displayName);
        if (!this.isMuted) {
            this.api.executeCommand('toggleAudio');
        }
        if (!this.isVideoMuted) {
            this.api.executeCommand('toggleVideo');
        }
        this.api.executeCommand('avatarUrl', this.avatarUrl);
        api.getRoomsInfo().then(rooms => {
            var participants = rooms.rooms[0].participants;
            for (var p of participants) {
                console.log(p);
                api.executeCommand('setParticipantVolume', {
                        participantID: p.id,
                        volume: 1
                    }
                );
            }
        })
    }
}


export {jitsiController}
