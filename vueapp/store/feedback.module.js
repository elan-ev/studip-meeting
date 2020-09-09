import Vue from "vue";
import ApiService from "@/common/api.service";

import {
    FEEDBACK_SUBMIT,
} from "./actions.type";

import {
    FEEDBACK_CLEAR,
    FEEDBACK_INIT
} from "./mutations.type";

const initialState = {
    feedback: {
        'room_id': '',
        'description': '',
        'browser_name': '',
        'browser_version': '',
        'os_name': '',
        'network_type': '',
        'cpu_type': '',
        'cpu_num': '',
        'cpu_old': '',
        'ram': '',
        'download_speed': '',
        'upload_speed': '',
    },
    network_types: {
        'bluetooth': 'Bluetooth',
        'cellular': 'Cellular',
        'ethernet': 'Ethernet',
        'wifi': 'Wifi',
        'wimax': 'Wimax',
        'other': 'Other',
        'unknown': 'Unknown',
        'none': 'None',
    }
};

const getters = {
    feedback(state) {
        return state.feedback;
    },
    network_types(state) {
        return state.network_types;
    },
};

export const state = { ...initialState };

export const actions = {
    async [FEEDBACK_SUBMIT](context, params) {
        params.cid = CID;
        return await ApiService.post('feedback', params);
    },
};

/* eslint no-param-reassign: ["error", { "props": false }] */
export const mutations = {
    [FEEDBACK_CLEAR](state) {
        state.feedback = {
            'room_id': '',
            'description': '',
            'browser_name': '',
            'browser_version': '',
            'os_name': '',
            'network_type': '',
            'cpu_type': '',
            'cpu_num': '',
            'cpu_old': '',
            'ram': '',
            'download_speed': '',
            'upload_speed': '',
        };
    },
    [FEEDBACK_INIT](state, room_id) {
        state.feedback.room_id = room_id;

        //nav
        var navUserAgent = navigator.userAgent;
        var browserName  = navigator.appName;
        var browserVersion  = ''+parseFloat(navigator.appVersion); 
        var tempNameOffset,tempVersionOffset,tempVersion;

        //browser name and version
        if ((tempVersionOffset=navUserAgent.indexOf("Opera"))!=-1) {
            browserName = "Opera";
            browserVersion = navUserAgent.substring(tempVersionOffset+6);
            if ((tempVersionOffset=navUserAgent.indexOf("Version"))!=-1) 
                browserVersion = navUserAgent.substring(tempVersionOffset+8);
        } else if ((tempVersionOffset=navUserAgent.indexOf("MSIE"))!=-1) {
            browserName = "Microsoft Internet Explorer";
            browserVersion = navUserAgent.substring(tempVersionOffset+5);
        } else if ((tempVersionOffset=navUserAgent.indexOf("Chrome"))!=-1) {
            browserName = "Chrome";
            browserVersion = navUserAgent.substring(tempVersionOffset+7);
        } else if ((tempVersionOffset=navUserAgent.indexOf("Safari"))!=-1) {
            browserName = "Safari";
            browserVersion = navUserAgent.substring(tempVersionOffset+7);
            if ((tempVersionOffset=navUserAgent.indexOf("Version"))!=-1) 
                browserVersion = navUserAgent.substring(tempVersionOffset+8);
        } else if ((tempVersionOffset=navUserAgent.indexOf("Firefox"))!=-1) {
            browserName = "Firefox";
            browserVersion = navUserAgent.substring(tempVersionOffset+8);
        } else if ( (tempNameOffset=navUserAgent.lastIndexOf(' ')+1) < (tempVersionOffset=navUserAgent.lastIndexOf('/')) ) {
            browserName = navUserAgent.substring(tempNameOffset,tempVersionOffset);
            browserVersion = navUserAgent.substring(tempVersionOffset+1);
            if (browserName.toLowerCase()==browserName.toUpperCase()) {
                browserName = navigator.appName;
            }
        }

        // trim version
        if ((tempVersion=browserVersion.indexOf(";"))!=-1)
            browserVersion=browserVersion.substring(0,tempVersion);
        if ((tempVersion=browserVersion.indexOf(" "))!=-1)
            browserVersion=browserVersion.substring(0,tempVersion);

        state.feedback.browser_name = browserName;
        state.feedback.browser_version = browserVersion;


        //download speed
        var download_baseurl = PLUGIN_ASSET_URL + '/speedtest/1mbtext.txt';
        var download_size = 1048576;
        let startTime, endTime;
        startTime = (new Date()).getTime();
        fetch(download_baseurl)
        .then(resp => resp.blob())
        .then(blob => {
            endTime = (new Date()).getTime();
            var duration = (endTime - startTime) / 1000;
            var bitsLoaded = download_size * 8;
            var speedBps = (bitsLoaded / duration).toFixed(2);
            var speedKbps = (speedBps / 1024).toFixed(2);
            var speedMbps = parseInt(speedKbps / 1024);
            state.feedback.download_speed = speedMbps;
        })

        //upload test
        var http = new XMLHttpRequest();
        // var startTime, endTime;
        var upload_baseurl = API_URL + '/feedback/uploadTest';
        var myData = "d="; // the raw data you will send
        for(var i = 0 ; i < 1022 ; i++) //if you want to send 1 kb (2 + 1022 bytes = 1024b = 1kb). change it the way you want
        {
            myData += "k"; // add one byte of data;
        }

        http.open("POST", upload_baseurl, true);

        http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        http.setRequestHeader("Content-length", myData .length);
        http.setRequestHeader("Connection", "close");

        http.onreadystatechange = () => {
            if(http.readyState == 4 && http.status == 200) {
                endTime = (new Date()).getTime();
                var duration = (endTime - startTime) / 1000;
                var bitsLoaded = download_size * 8;
                var speedMbps = parseInt((bitsLoaded / duration) / 1024 / 1024);
                state.feedback.upload_speed = speedMbps;
            }
        }
        startTime = (new Date()).getTime();
        http.send(myData);

        //network type
        var type = (navigator && navigator.connection) ? navigator.connection.type : 'none';
        if (type && Object.keys(state.network_types).includes(type)) {
            state.feedback.network_type = type;
        } else {
            state.feedback.network_type = 'unknown';
        }

        //OS type
        var os = '';
        var clientStrings = [
            {s:'Windows 10', r:/(Windows 10.0|Windows NT 10.0)/},
            {s:'Windows 8.1', r:/(Windows 8.1|Windows NT 6.3)/},
            {s:'Windows 8', r:/(Windows 8|Windows NT 6.2)/},
            {s:'Windows 7', r:/(Windows 7|Windows NT 6.1)/},
            {s:'Windows Vista', r:/Windows NT 6.0/},
            {s:'Windows Server 2003', r:/Windows NT 5.2/},
            {s:'Windows XP', r:/(Windows NT 5.1|Windows XP)/},
            {s:'Windows 2000', r:/(Windows NT 5.0|Windows 2000)/},
            {s:'Windows ME', r:/(Win 9x 4.90|Windows ME)/},
            {s:'Windows 98', r:/(Windows 98|Win98)/},
            {s:'Windows 95', r:/(Windows 95|Win95|Windows_95)/},
            {s:'Windows NT 4.0', r:/(Windows NT 4.0|WinNT4.0|WinNT|Windows NT)/},
            {s:'Windows CE', r:/Windows CE/},
            {s:'Windows 3.11', r:/Win16/},
            {s:'Android', r:/Android/},
            {s:'Open BSD', r:/OpenBSD/},
            {s:'Sun OS', r:/SunOS/},
            {s:'Chrome OS', r:/CrOS/},
            {s:'Linux', r:/(Linux|X11(?!.*CrOS))/},
            {s:'iOS', r:/(iPhone|iPad|iPod)/},
            {s:'Mac OS X', r:/Mac OS X/},
            {s:'Mac OS', r:/(MacPPC|MacIntel|Mac_PowerPC|Macintosh)/},
            {s:'QNX', r:/QNX/},
            {s:'UNIX', r:/UNIX/},
            {s:'BeOS', r:/BeOS/},
            {s:'OS/2', r:/OS\/2/},
            {s:'Search Bot', r:/(nuhk|Googlebot|Yammybot|Openbot|Slurp|MSNBot|Ask Jeeves\/Teoma|ia_archiver)/}
        ];
        for (var id in clientStrings) {
            var cs = clientStrings[id];
            if (cs.r.test(navUserAgent)) {
                os = cs.s;
                break;
            }
        }
        state.feedback.os_name =  os;
        state.feedback.cpu_type = (navigator && navigator.cpuClass) ? navigator.cpuClass : '';
        state.feedback.cpu_num = (navigator && navigator.hardwareConcurrency) ? navigator.hardwareConcurrency : '';
        state.feedback.ram = (navigator && navigator.deviceMemory) ? parseInt(navigator.deviceMemory) : '';
    },
};

export default {
  state,
  actions,
  mutations,
  getters
};
