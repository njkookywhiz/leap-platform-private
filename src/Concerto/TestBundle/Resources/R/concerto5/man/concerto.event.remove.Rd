\name{leap.event.remove}
\alias{leap.event.remove}
\title{
    Events
}
\description{
    Removing events.
}
\usage{
    leap.event.remove(name, fun)
}
\arguments{
    \item{name}{
        Event name
    }
    \item{fun}{
        Function
    }
}
\details{
}
\references{
}
\author{
    Kooky Whiz
}
\note{
}

\seealso{
}
\examples{
    leap.event.remove("onTemplateSubmit", function(response) {
        print(response)
    })
}

\keyword{ events }
