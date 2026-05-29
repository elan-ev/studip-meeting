import { createStore } from 'vuex';

import error from "./error.module";
import config from "./config.module";
import rooms_list from "./room.module";
import recording_list from "./recording.module";
import feedback from "./feedback.module";
import folder from "./folder.module";
import default_slide from "./default_slide.module";
import messages from "./messages.module";



const store = createStore({});

store.registerModule('error', error);
store.registerModule('config', config);
store.registerModule('rooms_list', rooms_list);
store.registerModule('recording_list', recording_list);
store.registerModule('feedback', feedback);
store.registerModule('folder', folder);
store.registerModule('default_slide', default_slide);
store.registerModule('messages', messages);

export default store;
