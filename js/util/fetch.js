import time from './time.js';

const fetcher = {

    $frame: document.querySelector('#frame'),
    $bar: document.querySelector('#bar'),
    $status: document.querySelector('#status'),
    $subtitle: document.querySelector('#subtitle'),
    $eventType: document.querySelector('#event-type'),

    endpoint: '',

    data: [],

    interval: null,
    barInMotion: false,

    init(ep) {
        this.endpoint = ep;

        this.getData();
        setTimeout(() => {
            this.getData();
            this.interval = setInterval(this.doInterval.bind(this), 60000);
        }, (time.secondsTilNextMin() + 1) * 1000);
    },

    doInterval() {
      this.getData();
    },

    playBar(remaining) {
        if (remaining <= 0) { remaining = 1 }
        if (this.barInMotion) { return; }
        this.$bar.style.setProperty('--animation-length', (remaining * 60) + 's');
        this.$bar.classList.add('bar--motion');
        this.barInMotion = true;
        setTimeout(() => {
            this.$bar.classList.remove('bar--motion');
            this.barInMotion = false;
        }, (remaining * 60) * 1000);
    },

    getData() {
        if (typeof this.endpoint !== 'string') {
            console.error('No endpoint', this);

            return [];
        }
        const rqst = new Request(
            this.endpoint
        );
        fetch(rqst)
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }

                return response.json();
            })
            .then((data) => {
                this.data = data;

                if (!data.success) {
                    console.error('Data issue', data, this);
                    this.processData(data);
                }

                this.processData(data);
            });
    },

    processData(data) {
        console.log(data, this);
        if (data.remaining === 1) {
            this.playBar(data.remaining);
        }
        if (typeof data.format !== 'undefined') {
            this.setValue(this.$status, data.format.status);
            this.setValue(this.$subtitle, data.format.subtitle);
            this.setValue(this.$eventType, data.format.type);

            this.$frame.className = '';
            this.$frame.classList.add(...data.format.class);
        }
    },

    setValue($el, val) {
        $el.innerHTML = val;
    }

}

export default fetcher;