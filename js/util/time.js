const time = {

    secondsTilNextMin() {
        const now = new Date();
        return 60 - now.getSeconds();
    }

}

export default time;