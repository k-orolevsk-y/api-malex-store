function include(url) {
    const script = document.createElement('script');
    script.src = url;
    document.head.appendChild(script);
}

include('https://www.google.com/recaptcha/api.js?render=6LeF8NwUAAAAAN52GqRPC-5GfuwnCAvuupTDSO9y');

window.onload = function () {
    grecaptcha.ready(function() {
        grecaptcha.execute('6LeF8NwUAAAAAN52GqRPC-5GfuwnCAvuupTDSO9y', {action: 'homepage'}).then(function(token) {
            let location2;
            const url = new URL(window.location.href);

            if(url.searchParams.get('redirect_uri') == null) {
                window.location.href = 'https://api.malex-store.ru/captcha?sid=' + url.searchParams.get('sid') + "&token=" + token;
            }
            else {
                window.location.href = 'https://api.malex-store.ru/captcha?sid=' + url.searchParams.get('sid') + "&redirect_uri=" + url.searchParams.get('redirect_uri') + "&token=" + token;
            }
        });
    });
}