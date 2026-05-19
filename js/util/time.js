const time = {

    secondsTilNextMin() {
        const now = new Date();
        return (60 - now.getSeconds() - now.getMilliseconds());
    },

    secondsTilNextQuarterMinute() {
        const now = new Date();
        const seconds = now.getSeconds();
        const milliseconds = now.getMilliseconds();
        const nextQuarterMinute = Math.ceil(seconds / 15) * 15;
        return (nextQuarterMinute - seconds - (milliseconds / 1000));
    }

}

export default time;
