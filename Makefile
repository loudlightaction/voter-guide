deploy:
	git push heroku main

tail-logs:
	heroku logs -t --app voter-guide

.PHONY: deploy
