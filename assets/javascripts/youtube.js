const API_KEY = "AIzaSyAw1bEYFQNzcXLLs4vnj9tlqjLZRoeitRk";
const CHANNEL_ID = "UCME2AtN2RIOtCeWx5h7kB3w";
const MAX_RESULTS = 6;

async function loadYouTubeVideos() {
    const videoContainer = document.getElementById("youtube-videos");

    try {
        const response = await fetch(
            `https://www.googleapis.com/youtube/v3/search?key=${API_KEY}&channelId=${CHANNEL_ID}&part=snippet,id&order=date&maxResults=${MAX_RESULTS}&type=video`
        );

        const data = await response.json();

        if (!data.items) {
            videoContainer.innerHTML = "<p>Unable to load videos.</p>";
            console.log(data);
            return;
        }

        let html = "";

        data.items.forEach(video => {
            html += `
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card shadow-sm border-0 h-100">
                        <img src="${video.snippet.thumbnails.high.url}" class="card-img-top" alt="${video.snippet.title}">
                        <div class="card-body">
                            <h5 class="card-title">${video.snippet.title}</h5>
                            <p class="card-text">
                                ${video.snippet.description.substring(0,100)}...
                            </p>
                            <a href="https://www.youtube.com/watch?v=${video.id.videoId}"
                               target="_blank"
                               class="btn btn-danger">
                               Watch Video
                            </a>
                        </div>
                    </div>
                </div>
            `;
        });

        videoContainer.innerHTML = html;

    } catch (error) {
        console.error(error);
        videoContainer.innerHTML = "<p>Error loading videos.</p>";
    }
}

loadYouTubeVideos();