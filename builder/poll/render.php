<?php $result = '<script id="'.($id ?? '').($position ?? '').'">fetch("/sarticles/poll/'.($value['id'] ?? '').'").then(response => {
    return response.text();
}).then((data) => {
    document.getElementById("'.($id ?? '').($position ?? '').'").insertAdjacentHTML("beforebegin", data);
}).catch(function(error){console.error("Request failed", error, ".")});
document.querySelector(".content").addEventListener("submit", function(e) {
    if(e.target && e.target.id == "poll'.($value['id'] ?? '').'") {
        fetch("/sarticles/poll/'.($value['id'] ?? '').'", {
            method: "POST",
            cache: "no-store",
            body: new FormData(e.target)
        }).then((response) => {
            return response.text();
        }).then((data) => {
            e.target.remove();
            document.getElementById("'.($id ?? '').($position ?? '').'").insertAdjacentHTML("beforebegin", data);
        }).catch(function(error) {
            console.error("Request failed", error, ".");
        });
    }
    e.preventDefault();
});
</script>';
echo $result;
