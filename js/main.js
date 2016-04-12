function toggleMore(target) {
    if (target.classList.contains("is-open")) {
        return target.classList.remove("is-open")
    }
    target.classList.add("is-open");
}

(function () {
    var more = document.getElementsByClassName("trigger-more");
    Array.prototype.map.call(more, function (link) {
        link.addEventListener("click", function(e) {
            e.preventDefault();
            toggleMore(e.target.parentElement);
        });
    });
})();
