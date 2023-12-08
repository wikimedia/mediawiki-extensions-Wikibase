FROM docker-registry.wikimedia.org/nodejs16-slim

RUN apt-get update && \
	apt-get install -y \
		ca-certificates \
		git

ARG UID=1000
ARG GID=1000

RUN addgroup --gid $GID runuser && adduser --uid $UID --gid $GID --disabled-password --gecos "" runuser

USER runuser
