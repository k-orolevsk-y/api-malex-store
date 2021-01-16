function include(url) {
    const script = document.createElement('script');
    script.src = url;
    document.head.appendChild(script);
}

include('https://www.google.com/recaptcha/api.js?render=6LeF8NwUAAAAAN52GqRPC-5GfuwnCAvuupTDSO9y');

window.onload = function () {
    grecaptcha.ready(function() {
        grecaptcha.execute('6LeF8NwUAAAAAN52GqRPC-5GfuwnCAvuupTDSO9y', {action: 'homepage'}).then(function(token) {
            const params = new URL(window.location.href).searchParams;

            if(params.get('redirect_uri') == null) {
                window.location.href = 'https://api.malex-store.ru/captcha?sid=' + params.get('sid') + "&token=" + token;
            } else {
                window.location.href = 'https://api.malex-store.ru/captcha?sid=' + params.get('sid') + "&redirect_uri=" + params.get('redirect_uri') + "&token=" + token;
            }
        });
    });
}
