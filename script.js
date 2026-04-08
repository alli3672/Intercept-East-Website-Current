function handleClick() {
  const img = document.getElementById('myImage');
  const btn = document.getElementById('shieldButton');
  const msg = document.getElementById('confirmationMessage');

  const wrappedSrc = '/site/images/Bogie_Wrapped.jpeg';

  img.onerror = function () {
    alert("Wrapped image could not load — but the file exists, so this means the path is wrong.");
    console.log("Failed to load:", wrappedSrc);
  };

  img.src = wrappedSrc;          // swap the image
  btn.style.display = 'none';    // hide button
  msg.style.display = 'block';   // show message
}
