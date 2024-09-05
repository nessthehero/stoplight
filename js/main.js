import fetcher from './util/fetch.js';

const main = {

    init(ep) {
        this.bindEvents();
        fetcher.init(ep);
    },

    bindEvents() {
        const navBar = document.querySelector('#navbar');

        document.querySelector('#fs').addEventListener('click', (event) => {
            main.fs();
        });

        document.querySelector('#open').addEventListener('click', () => {
            navBar.classList.replace('navbar--closed', 'navbar--open');
        });

        document.querySelector('#close').addEventListener('click', () => {
            navBar.classList.replace('navbar--open', 'navbar--closed');
        });

        document.querySelector('#calendar').addEventListener('change', (event) => {
            const select = event.target;
            if (typeof select.value !== 'undefined' && select.value !== null) {
                window.location.href = '?c=' + select.value;
            }
        });
    },

    fs() {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen();
        } else if (document.exitFullscreen) {
            document.exitFullscreen();
        }
    }

}

export { main };