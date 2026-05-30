/**newsletter form */
var form = document.getElementById("my-newsletter-form");
    
async function handleSubmit(event) {
  event.preventDefault();
  var status = document.getElementById("statuss");
  var data = new FormData(event.target);
  fetch(event.target.action, {
    method: form.method,
    body: data,
    headers: {
        'Accept': 'application/json'
    }
  }).then(response => {
    if (response.ok) {
      status.innerHTML = "Thanks for subscribing!";
      form.reset()
    } else {
      response.json().then(data => {
        if (Object.hasOwn(data, 'errorss')) {
          status.innerHTML = data["errorss"].map(error => error["message"]).join(", ")
        } else {
          status.innerHTML = "Oops! There was a problem submitting your form"
        }
      })
    }
  }).catch(error => {
    status.innerHTML = "Oops! There was a problem submitting your form"
  });
}
// Select the element you want to attach the event listener to
const backButton = document.getElementById('backButton');

// Add a click event listener to the element
backButton.addEventListener('click', function() {
    // Navigate back to the previous page
    history.back();
});


    document.getElementById("my-newsletter-form").addEventListener("submit", function(event) {
      event.preventDefault(); // Prevent the default form submission
      window.location.href = "index.html"; // Redirect to the desired page
    });
  



