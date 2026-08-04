const API_KEY = "";
const CHANNEL_ID = "UCME2AtN2RIOtCeWx5h7kB3w";
const MAX_RESULTS = 6;

const API_KEY = "AIzaSyAw1bEYFQNzcXLLs4vnj9tlqjLZRoeitRk";
const CHANNEL_ID = "UCME2AtN2RIOtCeWx5h7kB3w";
const MAX_RESULTS = 6;

async function loadYouTubeVideos() {

    const videoContainer = document.getElementById("youtube-videos");

    if (!videoContainer) {
        console.error("Element #youtube-videos not found.");
        return;
    }

    try {

        const response = await fetch(
            `https://www.googleapis.com/youtube/v3/search?part=snippet,id&type=video&order=date&maxResults=${MAX_RESULTS}&channelId=${CHANNEL_ID}&key=${API_KEY}`
        );

        const data = await response.json();

        if (!data.items) {
            console.log(data);
            videoContainer.innerHTML = "<p>Unable to load videos.</p>";
            return;
        }

        let html = "";

        data.items.forEach(video => {

            if (!video.id.videoId) return;

            html += `
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card shadow-sm border-0 h-100">
                        <img src="${video.snippet.thumbnails.medium.url}" class="card-img-top" alt="${video.snippet.title}">
                        <div class="card-body">
                            <h5>${video.snippet.title}</h5>
                            <p>${
                                video.snippet.description
                                    ? video.snippet.description.substring(0,100)
                                    : "No description available."
                            }</p>

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