localStorage.openpages = Date.now();
window.page_available = false;
var onLocalStorageEvent = function(e){
    if(e.key == "openpages"){
        // Emit that you're already available.
        localStorage.page_available = Date.now();
    }
    if(e.key == "page_available"){
        window.page_available = true;
    }
};
window.addEventListener('storage', onLocalStorageEvent, false);