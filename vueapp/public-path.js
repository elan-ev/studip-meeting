const url = new URL(document.currentScript.src);
__webpack_public_path__ = (url.toString().substr(0, url.toString().lastIndexOf("/"))
    + '/' + __webpack_public_path__);
